# FoodStarks - Laravel + PHP 8.4 + Node (build assets)
FROM php:8.4-cli-alpine AS base

RUN apk add --no-cache \
    git \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        intl \
        bcmath \
        opcache \
        pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node para compilar assets
RUN apk add --no-cache nodejs npm

WORKDIR /var/www/html

# Dependencias PHP
COPY composer.json composer.lock ./
# Usar HTTPS en lugar de SSH para GitHub (evita "cannot run ssh")
RUN git config --global url."https://github.com/".insteadOf "git@github.com:"
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize

# Dependencias Node y build de assets (Vite requiere devDependencies; package.json ya está en COPY . .)
RUN npm ci && npm run build && rm -rf node_modules

# Permisos storage y bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
