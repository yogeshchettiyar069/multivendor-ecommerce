#!/usr/bin/env sh
set -e

# -----------------------------------------------------------------------------
# Container entrypoint.
#
# Every container (app, queue, scheduler) waits for a writable MongoDB primary
# before starting. Only the "app" role performs one-time bootstrap (key, link,
# migrations, optional seed, cache warming) so the steps don't race across the
# worker containers. The script is idempotent and safe to re-run.
# -----------------------------------------------------------------------------

ROLE="${CONTAINER_ROLE:-app}"

echo "[entrypoint] ($ROLE) waiting for MongoDB to accept writes..."
php /usr/local/bin/wait-for-mongo.php

if [ "$ROLE" = "app" ]; then
    # Generate an app key only for local file-based env (on PaaS it is injected).
    if [ -z "${APP_KEY}" ] && [ -f .env ]; then
        echo "[entrypoint] generating APP_KEY..."
        php artisan key:generate --force
    fi

    echo "[entrypoint] linking storage..."
    php artisan storage:link 2>/dev/null || true

    echo "[entrypoint] running migrations (creates indexes)..."
    php artisan migrate --force

    if [ "${SEED_ON_BOOT}" = "true" ]; then
        echo "[entrypoint] seeding demo data..."
        php artisan db:seed --force || true
    fi

    if [ "${APP_ENV}" = "production" ]; then
        echo "[entrypoint] caching config/routes/views..."
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    else
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
    fi
fi

echo "[entrypoint] ($ROLE) starting: $*"
exec "$@"
