#!/bin/sh
set -e

# Esperar a que PostgreSQL esté listo (usando PHP para no depender de nc/pg_isready)
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

# Limpiar caché de vistas para que se use el manifest de Vite actual (public/build)
php artisan view:clear

exec "$@"
