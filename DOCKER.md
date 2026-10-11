# FoodStarks con Docker y PostgreSQL

## Requisitos

- Docker y Docker Compose instalados
- (Opcional) Archivo `.env` con variables para PostgreSQL

## Inicio rápido

### 1. Crear `.env` para Docker

Copia el ejemplo y configura PostgreSQL y APP_KEY:

```bash
cp .env.example .env
```

Genera la clave de aplicación y ponla en `.env` (obligatorio para que la app funcione):

```bash
# Con PHP instalado localmente:
php artisan key:generate --show

# O con Docker (se levanta postgres y el app solo para este comando):
docker compose run --rm app php artisan key:generate --show
```

Copia la línea que empiece por `APP_KEY=base64:...` y edita `.env` con la clave y la configuración de PostgreSQL:

```env
APP_NAME=FoodStarks
APP_URL=http://localhost:8000
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=foodstarks
DB_USERNAME=foodstarks
DB_PASSWORD=secret
```

### 2. Levantar los contenedores

```bash
docker compose up -d --build
```

La primera vez se construye la imagen (PHP 8.4, extensiones, Composer, Node, build de Vite) y se inicia PostgreSQL. El entrypoint espera a que la base esté lista y ejecuta las migraciones.

### 3. Acceder a la aplicación

- **App:** http://localhost:8000
- **PostgreSQL:** `localhost:5432` (usuario `foodstarks`, base `foodstarks`, contraseña `secret`)

### 4. Comandos útiles

```bash
# Ver logs de la app
docker compose logs -f app

# Ejecutar migraciones manualmente
docker compose exec app php artisan migrate

# Ejecutar seeders
docker compose exec app php artisan db:seed

# Composer (si el comando se queda colgado, usa -T y --no-interaction)
docker compose exec -T app composer dump-autoload --no-interaction
docker compose exec -T app composer install --no-interaction

# Entrar al contenedor de la app (shell: sh, no bash)
docker compose exec app sh

# Parar todo
docker compose down

# Parar y eliminar volúmenes (borra la base de datos)
docker compose down -v
```

### 5. 502 Bad Gateway (nginx)

Si nginx devuelve 502, suele ser porque el contenedor de la app **no llega a arrancar** Laravel: el entrypoint espera a PostgreSQL y si la conexión falla (usuario/contraseña incorrectos), nunca se ejecuta `php artisan serve`.

- **Causa:** Las variables `DB_USERNAME`, `DB_PASSWORD` y `DB_DATABASE` en tu `.env` no coinciden con las que usó PostgreSQL cuando se creó el volumen (Postgres guarda esas credenciales al inicializar el volumen).
- **Solución 1:** Poner en `.env` las mismas credenciales que cuando levantaste por primera vez (por defecto en `docker-compose.yml`: usuario `foodstarks`, contraseña `secret`, base `foodstarks`).
- **Solución 2:** Si quieres usar otras credenciales, reinicializar la base (se pierden los datos):
  ```bash
  docker compose down -v
  docker compose up -d
  ```
  Así Postgres se crea de nuevo con los valores actuales de `.env`.

Comprueba los logs: `docker logs foodstarks-app`. Si ves "Timeout esperando PostgreSQL" en bucle, es un fallo de conexión/credenciales.

### 6. Ver cambios después de editar código (Vue, PHP, etc.)

Docker usa **caché** de capas: si no reconstruís sin caché, puede seguir sirviendo una versión vieja. Para que se apliquen tus cambios:

1. **Reconstruir la imagen sin caché y levantar de nuevo:**
   ```bash
   docker compose build --no-cache app
   docker compose up -d --force-recreate
   ```

2. **Refresco forzado en el navegador:** `Ctrl+Shift+R` (o Cmd+Shift+R en Mac) para no usar caché del navegador.

3. **(Opcional)** Si aún ves algo viejo, limpiar caché de Laravel dentro del contenedor:
   ```bash
   docker compose exec app php artisan optimize:clear
   ```

El entrypoint ya ejecuta `php artisan view:clear` al arrancar para que se use el manifest de Vite actual.

## Servicios

| Servicio   | Imagen / Build | Puerto | Descripción                    |
|-----------|----------------|--------|--------------------------------|
| **app**   | Dockerfile     | 8000   | Laravel (PHP 8.4, `php artisan serve`) |
| **postgres** | postgres:16-alpine | 5432 | PostgreSQL 16                 |

## Variables de entorno (docker-compose)

Puedes definir en tu `.env` (en la raíz del proyecto) o exportar antes de `docker compose up`:

- `APP_PORT` — puerto del host donde se expone la app (default: **80** en local). En producción podés usar `APP_PORT=8035` para exponer en 8035.
- `DB_DATABASE` (default: foodstarks)
- `DB_USERNAME` (default: foodstarks)
- `DB_PASSWORD` (default: secret)
- `APP_KEY` — obligatorio para que la app funcione

## Desarrollo local con Docker

El `docker-compose.yml` monta el código actual en el contenedor (`.:/var/www/html`). Los directorios `vendor` y `node_modules` se mantienen en volúmenes del contenedor para no pisar los tuyos.

Si quieres cambiar código PHP o Vue, edita en tu máquina y recarga en el navegador. Si cambias dependencias (`composer.json` o `package.json`), reconstruye la imagen:

```bash
docker compose up -d --build
```

Para compilar assets de frontend sin reconstruir la imagen, puedes ejecutar dentro del contenedor:

```bash
docker compose exec app sh
npm ci && npm run build
exit
```

O compilar en tu máquina (con Node instalado) y los archivos generados en `public/build` se usarán al montar el proyecto.

## Deploy en producción (GitHub Actions → GHCR → Dokploy)

Mismo esquema que `noticlub`. `.github/workflows/ci-cd.yml`:

1. **build** — construye el `Dockerfile` de la raíz. En los PR solo valida que compile; en push a `main`
   publica `ghcr.io/claubuck/foodstarks` con tags `latest` y el SHA.
2. **deploy** — POST al webhook de Dokploy (secret de GitHub **`DOKPLOY_DEPLOY_WEBHOOK`**).

> `docker-compose.yml` es solo para desarrollo y usa `docker/Dockerfile.dev`.

La imagen corre **un solo contenedor** con `supervisord` (`docker/supervisord.conf`): servidor web
(`artisan serve`, puerto **8000**), **SSR** de Inertia, **Horizon** (colas en Redis), **Reverb**
(websockets, puerto **8080**) y **scheduler**. `docker/entrypoint.sh` espera a PostgreSQL, crea el link
de storage, migra si `RUN_MIGRATE=true` y ejecuta `artisan optimize`.

El bundle SSR se compila autocontenido (`ssr.noExternal` en `vite.config.js`): en producción solo hace
falta el binario de Node. La key de Reverb se pasa al navegador en runtime (`app.blade.php`), así que no
hacen falta variables `VITE_*` al compilar.

### Configuración en Dokploy

- **Aplicación**: provider *Docker*, imagen `ghcr.io/claubuck/foodstarks:latest` (si el paquete de GHCR es
  privado, cargá credenciales del registry con un token con `read:packages`).
- **Bases**: crear un PostgreSQL y un Redis en el mismo proyecto de Dokploy y usar sus hosts internos.
- **Dominios**: `app.tudominio.com` → puerto `8000`; `ws.tudominio.com` → puerto `8080` (Reverb), ambos con HTTPS.
- **Volumen persistente**: `/var/www/html/storage/app/public`.
- **Webhook**: copiar la URL de *Deployments → Webhook* y cargarla en GitHub como `DOKPLOY_DEPLOY_WEBHOOK`.

| Variable | Valor |
| --- | --- |
| `APP_KEY` | Obligatoria y fija (`php artisan key:generate --show`) |
| `APP_URL` | `https://app.tudominio.com` |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `DB_CONNECTION` / `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | `pgsql` + datos del PostgreSQL de Dokploy |
| `REDIS_HOST` / `REDIS_PORT` / `REDIS_PASSWORD` | Datos del Redis de Dokploy |
| `QUEUE_CONNECTION` / `CACHE_STORE` / `SESSION_DRIVER` | `redis` (Horizon necesita colas en Redis) |
| `BROADCAST_CONNECTION` | `reverb` |
| `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` | Valores propios (no usar los de desarrollo) |
| `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` | `127.0.0.1` / `8080` / `http` (Laravel → Reverb dentro del contenedor) |
| `REVERB_PUBLIC_HOST` / `REVERB_PUBLIC_PORT` / `REVERB_PUBLIC_SCHEME` | `ws.tudominio.com` / `443` / `https` (navegador → Reverb) |
| `REVERB_ALLOWED_ORIGINS` | `app.tudominio.com` (opcional, por defecto `*`) |
| `RUN_MIGRATE` | `true` para ejecutar `migrate --force` en cada arranque |
| `WEB_WORKERS` | Procesos del servidor web (por defecto `4`) |
| `MP_ACCESS_TOKEN` / `MP_PUBLIC_KEY` | Mercado Pago |
