#!/bin/bash
set -e

# Wait for database to be ready (if needed)
# You can add database connection checks here

# Generate application key if not set
php artisan key:generate --force || true

# Clear and cache configuration
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Generate Swagger documentation
php artisan l5-swagger:generate || true

# Run migrations (optional - uncomment if you want auto-migrations)
# php artisan migrate --force || true

# Start the application
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}

