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

# Read APP_KEY from .env file if it exists and is valid
if [ -f .env ] && grep -q "^APP_KEY=base64:" .env; then
    APP_KEY_FROM_FILE=$(grep "^APP_KEY=" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    if [ ! -z "$APP_KEY_FROM_FILE" ] && [[ "$APP_KEY_FROM_FILE" =~ ^base64: ]]; then
        export APP_KEY="$APP_KEY_FROM_FILE"
        echo "APP_KEY loaded from .env file"
    fi
fi

# Generate application key if not set or invalid
# Laravel requires keys to be in format: base64:...
if [ -z "$APP_KEY" ] || [[ ! "$APP_KEY" =~ ^base64: ]]; then
    echo "Generating application key..."
    # Generate key manually in correct format (Laravel requires base64: prefix)
    KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")
    
    # Update .env file
    if grep -q "^APP_KEY=" .env; then
        sed -i "s|^APP_KEY=.*|APP_KEY=$KEY|" .env
    else
        echo "APP_KEY=$KEY" >> .env
    fi
    
    # Export to environment
    export APP_KEY="$KEY"
    echo "Application key generated and set"
else
    echo "APP_KEY is already set and valid"
    # Ensure it's also in .env file if it's only in environment
    if ! grep -q "^APP_KEY=" .env; then
        echo "APP_KEY=$APP_KEY" >> .env
    fi
fi

# Verify key is set
if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY is still not set after generation attempt"
    exit 1
fi

echo "APP_KEY is set: ${APP_KEY:0:20}..."

# Clear and cache configuration (these commands need APP_KEY)
# Use --env to ensure environment variables are used
php artisan config:clear --env=production || true
php artisan route:clear || true
php artisan view:clear || true

# Generate Swagger documentation
php artisan l5-swagger:generate || true

# Run migrations (optional - uncomment if you want auto-migrations)
# php artisan migrate --force || true

# Start the application with APP_KEY in environment
exec env APP_KEY="$APP_KEY" php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
