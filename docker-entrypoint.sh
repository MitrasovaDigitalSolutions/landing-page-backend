#!/bin/sh
set -e

# Create storage symlink if missing
php artisan storage:link || true

# Apply pending migrations before serving traffic
php artisan migrate --force

# Execute main command
exec "$@"
