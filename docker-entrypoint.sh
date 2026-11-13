#!/bin/sh
set -e

# Create storage symlink if missing
php artisan storage:link || true

# Execute main command
exec "$@"
