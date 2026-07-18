#!/bin/sh
# Production entrypoint: wait for MySQL, migrate + seed, cache config, then serve.
set -e
cd /app

echo "==> Waiting for database (${DB_HOST}:${DB_PORT:-3306})..."
i=0
until php -r '
    try {
        new PDO(
            "mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: "3306"),
            getenv("DB_USERNAME"),
            getenv("DB_PASSWORD")
        );
        exit(0);
    } catch (Throwable $e) { exit(1); }
' 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -ge 60 ]; then
        echo "!! Database not reachable after 120s, aborting." >&2
        exit 1
    fi
    sleep 2
done
echo "==> Database is up."

# Schema + data. The seeder is idempotent (truncate + reinsert the 590 samples).
php artisan migrate --force
php artisan db:seed --force

# Production caches (env is already present in the container environment).
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting FrankenPHP."
exec frankenphp run --config /etc/caddy/Caddyfile
