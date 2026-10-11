#!/bin/sh
set -e

# Borrar caché de providers (evita BreezeServiceProvider si Breeze no está instalado)
rm -f bootstrap/cache/services.php bootstrap/cache/packages.php 2>/dev/null || true
# Limpiar caché de config para usar variables del contenedor (REDIS_HOST=redis, etc.)
php artisan config:clear 2>/dev/null || true
# Limpiar caché de rutas
php artisan route:clear 2>/dev/null || true

# Esperar a que PostgreSQL esté listo
if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" = "pgsql" ]; then
  echo "Esperando a PostgreSQL en $DB_HOST:${DB_PORT:-5432}..."
  php -r "
    \$host = getenv('DB_HOST');
    \$port = getenv('DB_PORT') ?: '5432';
    \$dbname = getenv('DB_DATABASE') ?: 'postgres';
    \$max = 60;
    \$i = 0;
    while (\$i < \$max) {
      try {
        \$pdo = new PDO(
          \"pgsql:host=\$host;port=\$port;dbname=\$dbname\",
          getenv('DB_USERNAME') ?: 'postgres',
          getenv('DB_PASSWORD') ?: ''
        );
        echo \"PostgreSQL listo.\n\";
        exit(0);
      } catch (Throwable \$e) {
        \$i++;
        if (\$i >= \$max) { echo \"Timeout esperando PostgreSQL.\n\"; exit(1); }
        sleep(1);
      }
    }
  "
fi

# Ejecutar migraciones si se indica
if [ "$RUN_MIGRATE" = "true" ]; then
  php artisan migrate --force --no-interaction || true
fi

# Limpiar cachés para que se use manifest de Vite y config/URL actual (evita assets por http tras proxy HTTPS)
php artisan config:clear
php artisan view:clear

exec "$@"
