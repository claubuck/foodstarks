# FoodStarks - imagen de PRODUCCIÓN (GitHub Actions -> GHCR -> Dokploy).
# Para desarrollo local con docker-compose se usa docker/Dockerfile.dev.

# =====================================================================
# Stage 1 - Dependencias PHP (Composer)
# Genera vendor/ una sola vez: lo usan el build de Vite (ssr.js importa
# Ziggy desde vendor/) y la imagen final.
# =====================================================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN git config --global url."https://github.com/".insteadOf "git@github.com:"
RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --ignore-platform-reqs

# =====================================================================
# Stage 2 - Frontend build (Vite + Inertia/Vue, cliente y SSR)
# Base Debian (glibc) para que los binarios nativos de esbuild/rollup
# resuelvan sin sorpresas.
# =====================================================================
FROM node:22-bookworm-slim AS frontend

WORKDIR /app

# Cachear capa de npm: copiar solo manifests primero
COPY package.json package-lock.json ./
RUN npm ci

# Resto del codigo (lo necesita Vite para resolver imports + Tailwind)
COPY . .

# vendor/ desde el stage de Composer (Ziggy para cliente y SSR)
COPY --from=vendor /app/vendor ./vendor

# Genera public/build (cliente) y bootstrap/ssr (servidor SSR autocontenido)
RUN npm run build

# =====================================================================
# Stage 3 - Aplicacion Laravel + PHP 8.4
# Un solo contenedor con supervisord: web, SSR, Horizon, scheduler y Reverb.
# =====================================================================
FROM php:8.4-cli-alpine AS app

RUN apk add --no-cache \
    supervisor \
    libstdc++ \
    libgcc \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        intl \
        bcmath \
        opcache \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# php.ini de producción + ajustes propios
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-foodstarks.ini"

# Node (solo el binario) para el servidor SSR de Inertia.
# Imagen alpine (musl) para que el binario sea compatible con esta base.
COPY --from=node:22-alpine /usr/local/bin/node /usr/local/bin/node
RUN node -v

# Composer (para dump-autoload)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Codigo de la app
COPY . .

# Vendor desde el stage de Composer
COPY --from=vendor /app/vendor ./vendor

# Assets compilados y bundle SSR desde el stage de Vite
COPY --from=frontend /app/public/build ./public/build
COPY --from=frontend /app/bootstrap/ssr ./bootstrap/ssr

# Autoloader optimizado para produccion
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# Permisos storage y bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
# Asegurar LF (evitar "no such file or directory" por CRLF en Windows)
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint /etc/supervisord.conf \
    && chmod +x /usr/local/bin/entrypoint

# 8000: web (Laravel) | 8080: websockets (Reverb)
EXPOSE 8000 8080

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
