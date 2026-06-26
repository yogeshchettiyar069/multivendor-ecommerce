#!/usr/bin/env sh
set -e

# Render injects $PORT (usually 10000). Bake it into the nginx config.
export PORT="${PORT:-10000}"
sed "s/{{PORT}}/${PORT}/g" /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

cd /var/www/html

# Create indexes / run any migrations against Atlas. Idempotent and safe on every boot.
php artisan migrate --force || echo "migrate skipped/failed (continuing)"

# Optionally seed the live demo (vendors, catalogue, sample orders) on first boot.
# The seeder is guarded — it skips if demo data already exists — so this is safe to
# leave on across deploys.
if [ "${SEED_ON_BOOT}" = "true" ]; then
    php artisan db:seed --force || echo "seed skipped/failed (continuing)"
fi

# Expose the private storage symlink (no-op if it already exists).
php artisan storage:link 2>/dev/null || true

# Cache config/routes/views for production performance.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec supervisord -c /etc/supervisord.conf
