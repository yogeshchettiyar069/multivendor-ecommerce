# syntax=docker/dockerfile:1

# =============================================================================
# Stage 1 — Build front-end assets (Inertia/React/TS via Vite)
# =============================================================================
FROM node:22-alpine AS frontend

WORKDIR /app

# Install JS deps from lockfile for reproducible builds.
COPY package.json package-lock.json ./
RUN npm ci

# Build the production bundle into public/build.
COPY . .
RUN npm run build


# =============================================================================
# Stage 2 — PHP runtime (PHP-FPM 8.3 + extensions + Composer deps)
# =============================================================================
FROM php:8.3-fpm-alpine AS app

# --- System libraries -------------------------------------------------------
# Runtime libs are kept; build-only libs are added as a virtual package and
# removed afterwards to keep the final image lean.
RUN apk add --no-cache \
        bash \
        git \
        icu-libs \
        libpng \
        libjpeg-turbo \
        freetype \
        libzip \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        openssl-dev \
    # Core PHP extensions
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        pcntl \
        zip \
        opcache \
    # PECL extensions: MongoDB driver + Redis
    && pecl install mongodb redis \
    && docker-php-ext-enable mongodb redis \
    && apk del .build-deps

# --- Composer ---------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

WORKDIR /var/www/html

# Install PHP dependencies first (better layer caching). Scripts are skipped
# until the full source is present so artisan-based scripts can run.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# --- Application source -----------------------------------------------------
COPY . .

# Bring in the compiled front-end assets from the frontend stage.
COPY --from=frontend /app/public/build ./public/build

# Finalise the autoloader and run package discovery.
RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-autoload-dump

# --- PHP configuration ------------------------------------------------------
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

# --- Permissions & entrypoint ----------------------------------------------
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
COPY docker/wait-for-mongo.php /usr/local/bin/wait-for-mongo.php
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]


# =============================================================================
# Stage 3 — Nginx web server (serves static public/ assets, proxies PHP to FPM)
# =============================================================================
FROM nginx:1.27-alpine AS web

# Static assets only: the framework PHP files live in the FPM container, which
# resolves SCRIPT_FILENAME at the same /var/www/html path.
COPY public /var/www/html/public
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
