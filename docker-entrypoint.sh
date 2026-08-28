#!/usr/bin/env sh
set -eu

cd /var/www/html
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Local bind mounts omit vendor; the production image already includes it.
if [ ! -f vendor/autoload.php ]; then
    echo "Installing Composer dependencies for the bind-mounted application..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -f .env ]; then
    if [ -f .env.docker.example ]; then cp .env.docker.example .env
    fi
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

if [ ! -e public/storage ]; then php artisan storage:link --force || true; fi
exec "$@"
