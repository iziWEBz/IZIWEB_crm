#!/bin/sh
set -e

# Config/route/view caches are built at start, not in the image: the build has
# no APP_KEY, DB_URL or any other runtime value, and a cache built without them
# would freeze empty settings into the container.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Swagger documentation (storage/api-docs) is generated from the annotations
# in the code. A failure here must not keep the API down.
php artisan l5-swagger:generate || echo "WARNING: l5-swagger:generate failed, API docs are unavailable"

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
