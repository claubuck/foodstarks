#!/bin/sh
set -e

# Procesos paralelos del servidor web (supervisord.conf lo lee del entorno)
export WEB_WORKERS="${WEB_WORKERS:-4}"

# Generar APP_KEY si no existe (primera ejecución)
if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "base64:" ]; then
  echo "AVISO: APP_KEY vacía; se genera una temporal (defínela en Dokploy para que persista)."
  export APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
fi

# Esperar a que PostgreSQL esté listo
if [ -n "${DB_HOST}" ] && [ "${DB_CONNECTION:-pgsql}" = "pgsql" ]; then
  export WAIT_DSN="pgsql:host=${DB_HOST};port=${DB_PORT:-5432};dbname=${DB_DATABASE}"
  export WAIT_USER="${DB_USERNAME}"
  export WAIT_PASS="${DB_PASSWORD}"
  until php -r 'try { new PDO(getenv("WAIT_DSN"), getenv("WAIT_USER"), getenv("WAIT_PASS")); exit(0); } catch (Throwable $e) { exit(1); }' 2>/dev/null; do
    echo "Esperando PostgreSQL..."
    sleep 2
  done
  unset WAIT_DSN WAIT_USER WAIT_PASS
fi

# Carpetas de runtime (el .dockerignore excluye su contenido y el volumen
# de storage/app/public puede montarse vacío)
mkdir -p storage/app/public storage/logs \
  storage/framework/cache/data storage/framework/sessions storage/framework/views \
  bootstrap/cache

# Symlink de storage público (public/storage -> storage/app/public).
# Idempotente: --force recrea el link si hace falta.
php artisan storage:link --force || true

# Migraciones si se indica
if [ "${RUN_MIGRATE}" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

# Cachear configuración, rutas, vistas y eventos con las variables del entorno
php artisan optimize

# Los comandos anteriores corren como root: devolver storage y cache a www-data
# (incluye el volumen de storage/app/public montado por Dokploy).
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
