# =============================================================================
# Production image for the K-Means network-disturbance dashboard.
# Multi-stage: PHP deps (composer) -> frontend assets (vite) -> FrankenPHP runtime.
# =============================================================================

# --- Stage 1: PHP dependencies ----------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

# --- Stage 2: Frontend assets (Tailwind + Chart.js via Vite) ----------------
# vendor is copied in so Tailwind v4 can scan Laravel's pagination Blade views
# (referenced by @source in resources/css/app.css) and keep them styled.
FROM node:24-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# --- Stage 3: Runtime -------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4 AS app
WORKDIR /app

# PHP extensions Laravel + MySQL need.
RUN install-php-extensions pdo_mysql opcache zip bcmath pcntl

# Production php.ini and the container's Caddyfile.
COPY docker/frankenphp/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

# Application code + prebuilt deps and assets.
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Runtime user owns the writable paths; FrankenPHP can bind :80 unprivileged
# because the binary carries cap_net_bind_service in this image.
RUN cp docker/frankenphp/entrypoint.sh /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache /data /config

USER www-data

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
