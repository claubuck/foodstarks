# FoodStarks

SaaS de gestión para restaurantes (multi-restaurante, multi-sucursal): mesas, toma de pedidos (POS),
cobro, productos, reservas, empleados y suscripciones. UI y textos en español (rioplatense).

## Stack

- Laravel 12 + PHP ^8.4 · Inertia v2 + Vue 3 (`<script setup>`) + Tailwind 3 · Ziggy (`route()` en JS)
- **Producción usa PostgreSQL** (también en docker) · SQLite solo en local y tests: las queries y migraciones
  tienen que funcionar en ambos (ojo con SQL crudo, `ilike`, tipos JSON y enums) · Redis (cache, sesión y colas en prod)
- Horizon (colas) · Reverb + laravel-echo/pusher-js (tiempo real) · SSR de Inertia (`resources/js/ssr.js`)
- Auth: Breeze (Vue). Tests con Pest.

## Comandos

```bash
composer install && npm ci
npm run dev               # Vite
npm run build             # cliente + SSR (bootstrap/ssr)
php artisan migrate --seed  # DatabaseSeeder: planes + datos demo
php artisan db:seed --class=SubscriptionPlanSeeder   # solo planes (idempotente, necesario en prod)
php artisan subscription:activate {email} --plan=basico --days=30  # asignar plan (days 0 = sin vencimiento)
php artisan tables:sync-status                       # recalcular estado de mesas (corre cada 5 min en el scheduler)
php artisan user:check {email}                       # diagnóstico de usuario/restaurante/suscripción
```

Local en Windows (Laragon) el PHP es 8.2 sin `pdo_sqlite`: para tests usar
`php -d extension=pdo_sqlite vendor/bin/pest` (con `php artisan test` el `-d` no llega al subproceso).
Al regenerar `composer.lock` usar `--ignore-platform-reqs` (composer.json exige PHP ^8.4).

**Tests:** Pest con SQLite en memoria. Helper `restaurantUser($role, $plan)` en `tests/Pest.php` crea
restaurante + sucursal + plan + usuario. Flujos cubiertos en `tests/Feature/PosFlowTest.php` y
`AccountFlowTest.php`. CI corre los tests; Pint no (el código heredado no está formateado).

## Arquitectura

### Multi-tenant
- `Restaurant` (UUID) 1→N `Branch` (UUID) · `User` (UUID) pertenece a un restaurante.
- El resto de los modelos usa id autoincremental y tiene `restaurant_id` + `branch_id` (products, orders,
  tables, reservations). `ProductCategory` es por restaurante (sin branch).
- La sucursal actual vive en sesión (`current_branch_id`). El middleware `branch.set`
  (`SetCurrentBranch`) la valida o asigna la primera activa y la deja en `request->attributes`.
  En controladores: `$this->currentBranchId($request)` (en `Controller` base).
- **Cada query y cada acción sobre un modelo debe filtrar/chequear `restaurant_id` y `branch_id`**
  (patrón actual: `if ($m->restaurant_id !== $user->restaurant_id || $m->branch_id !== $branchId) abort(404);`).
  Las Policies existen pero son stubs sin usar.
- Props compartidas (`HandleInertiaRequests`): `auth.user`, `auth.restaurant`, `auth.branches`,
  `auth.current_branch_id`, `session.flash.{success,error,info}`. El selector de sucursal del layout
  hace POST a `branch.switch`.

### Roles (`users.role` enum)
`owner`, `admin`, `manager`, `chef`, `waiter`. Se controlan con closures en el constructor de cada
controlador (`$this->middleware(...)`, funciona porque `Controller` extiende `Illuminate\Routing\Controller`):
- Empleados, restaurante, sucursales, categorías: `owner`/`manager`.
- `/superadmin/*` y Horizon: Gate `superadmin` = email en `SUPERADMIN_EMAILS` (`config('app.superadmins')`).
  Es el operador de la plataforma, no un rol del restaurante. Prop compartida `auth.is_superadmin`.
- POS, pedidos, mesas, productos, reservas: cualquier usuario autenticado del restaurante.

### Suscripciones
- `SubscriptionPlan` (slugs `free`, `basico`, `profesional`, `empresa`) con `max_branches` / `max_users`.
- Vigente = scope `Subscription::current()` (trial/active y `current_period_end` null o futuro).
  Usar siempre `$restaurant->activeSubscription()` / `currentPlan()`; nunca filtrar fechas a mano.
- `$restaurant->assignPlan($plan, ?$days)` cancela la vigente y crea la nueva (registro, comando y panel).
- El registro crea restaurante + sucursal "Casa matriz" + `owner` + plan `free` sin vencimiento (si faltan
  los planes, corre el seeder). El entrypoint también siembra los planes cuando `RUN_MIGRATE=true`.
- Middleware `subscription.active` protege el panel (perfil y páginas de plan quedan fuera).
- No hay cobro online (Mercado Pago no integrado): el restaurante pide el plan por mail a `SUPPORT_EMAIL`
  (`PlanGrid.vue`) y el superadmin lo asigna en `/superadmin`.

### Flujo principal (POS)
1. `Dashboard` (`DashboardController`): stats del día, grilla de mesas con su pedido activo y
   pedidos sin mesa. Click en mesa → `TableModal` (ir a tomar pedido / agregar ítems / cobrar).
2. `sales.create` (`Sales/Create.vue`): catálogo + carrito. Query `table_id`/`order_id`; si la mesa tiene
   pedido abierto se trabaja sobre él. Lo ya pedido se muestra aparte y **solo se envían ítems nuevos**.
3. `orders.store`: destino = `order_id`, o el pedido activo de la mesa, o uno nuevo. **El precio sale de
   `products.price`** (el cliente no lo manda). Total con `Order::recalculateTotal()`.
4. `orders.cobrar` (acepta `payment_method`): `completed` + `paid`. `orders.update` cambia estado desde
   `Orders/Show`; `orders.destroy` borra detalles (FK con cascade desde la migración 2026_10_10).
- Estados de pedido: `pending`, `in_progress`, `completed`, `cancelled`. Tipos: `dine_in`, `takeaway`,
  `delivery`. Pago: `cash`, `card`, `transfer` (se elige al crear el pedido).
- Estados de mesa: `available`, `occupied`, `reserved`. **No setearlos a mano: llamar `$table->syncStatus()`**
  (occupied si hay pedido activo; reserved si hay reserva pending/confirmed entre -30 min y +2 h).
  Pedidos y reservas lo llaman; el scheduler corre `tables:sync-status` cada 5 min.
- Una mesa con pedido abierto o reservas futuras no se puede borrar; las reservas pasadas quedan con `table_id` null.

### Tiempo real
- Evento `App\Events\OrderUpdated($branchId)` → canal privado `branch.{branchId}`, nombre `order.updated`.
  Se dispara en todas las mutaciones de pedidos (`OrderController::broadcastOrderUpdated`, tolera
  Reverb caído).
- Autorización del canal en `routes/channels.php` (la sucursal debe ser del restaurante del usuario).
- `Dashboard.vue` escucha `.order.updated` y hace `router.reload({ only: [...] })`; además hace
  polling cada 15 s como fallback.
- Config del navegador inyectada en runtime por `app.blade.php` (`window.REVERB_APP_KEY`,
  `REVERB_WS_HOST/PORT/SCHEME`, desde `broadcasting.connections.reverb.public_*`), leída en
  `resources/js/bootstrap.js`. No depender de variables `VITE_*` (la imagen se compila en CI sin `.env`).
- Ojo: en `config/broadcasting.php`, si `REDIS_HOST === 'redis'` el host de Reverb pasa a ser `reverb`
  (pensado para docker-compose).

## Convenciones

- Rutas en `routes/web.php`, nombradas y dentro de `auth` + `verified` + `subscription.active` + `branch.set`.
  Algunas URLs no son REST (`/orders/store`, `/products/update/{id}`); siempre usar el nombre de ruta.
- Páginas en `resources/js/Pages/<Modulo>/<Accion>.vue`, layout `AuthenticatedLayout.vue` (menú lateral).
  Formularios con `useForm` de Inertia; feedback con flash `success`/`error`.
- Validación: FormRequests en `app/Http/Requests` (muchos son stubs) o `$request->validate` inline.
- Imágenes subidas al disco `public` (`Restaurants/{id}/Products`), se guarda la URL completa con `asset()`.
- Stubs sin rutas ni UI (generados con `make:model -a`): Delivery, EmployeeShift, Inventory,
  PaymentMethod, Supplier, OrderDetail (controladores, requests y policies vacíos).
- Diagnóstico solo para superadmin: `/superadmin/realtime-test`, `/superadmin/queue-test`,
  `/superadmin/queue-diagnostic`.
- `orders.user_id` borra en cascada: antes de borrar un usuario se reasignan sus pedidos (ver
  `EmployeeController::destroy` y `ProfileController::destroy`). El `owner` no puede borrarse.
- Empleados (`owner`/`manager`): alta, edición y baja; límite `max_users` del plan vigente.
- Imágenes de producto: validadas como imagen (jpg/png/webp/gif, 5 MB). Al editar sin archivo se conserva la actual.
- Zona horaria `APP_TIMEZONE` (default Buenos Aires): define el corte de "ventas del día".

## Deploy

GitHub Actions (`.github/workflows/ci-cd.yml`) → imagen en GHCR → webhook de Dokploy. Imagen única con
supervisord (web :8000, SSR, Horizon, Reverb :8080, scheduler); el entrypoint espera Postgres, migra
si `RUN_MIGRATE=true` y corre `optimize`. Detalle de variables y dominios en `DOCKER.md`.
`docker-compose.yml` + `docker/Dockerfile.dev` son solo para desarrollo.
