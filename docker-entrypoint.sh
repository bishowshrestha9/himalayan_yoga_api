#!/bin/bash
set -e

# Ensure .env file exists
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        echo "APP_NAME=Laravel" > .env
        echo "APP_ENV=${APP_ENV:-production}" >> .env
        echo "APP_DEBUG=${APP_DEBUG:-false}" >> .env
        echo "APP_URL=${APP_URL:-http://localhost}" >> .env
    fi
fi

# Generate application key if not set or invalid
# Laravel requires keys to be in format: base64:...
if [ -z "$APP_KEY" ] || [[ ! "$APP_KEY" =~ ^base64: ]]; then
    echo "Generating application key..."
    # Try artisan key:generate first
    if php artisan key:generate --force --no-interaction 2>/dev/null; then
        echo "Application key generated successfully"
    else
        echo "Warning: artisan key:generate failed, generating key manually..."
        # Fallback: generate key manually in correct format
        KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")
        # Update .env file (Docker uses GNU sed)
        if grep -q "^APP_KEY=" .env; then
            sed -i "s|^APP_KEY=.*|APP_KEY=$KEY|" .env
        else
            echo "APP_KEY=$KEY" >> .env
        fi
        export APP_KEY="$KEY"
        echo "Application key generated manually"
    fi
else
    echo "APP_KEY is already set and valid"
    # Ensure it's also in .env file if it's only in environment
    if ! grep -q "^APP_KEY=" .env; then
        echo "APP_KEY=$APP_KEY" >> .env
    fi
fi

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
