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

# Entrar al contenedor de la app
docker compose exec app sh

# Parar todo
docker compose down

# Parar y eliminar volúmenes (borra la base de datos)
docker compose down -v
```

### 5. Ver cambios después de editar código (Vue, PHP, etc.)

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
