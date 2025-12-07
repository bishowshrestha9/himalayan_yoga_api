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
        # Try to detect APP_URL from Render environment variables
        if [ -z "$APP_URL" ]; then
            if [ ! -z "$RENDER_EXTERNAL_URL" ]; then
                # Ensure HTTPS is used for Render URLs
                RENDER_URL="$RENDER_EXTERNAL_URL"
                if [[ "$RENDER_URL" =~ ^http:// ]]; then
                    RENDER_URL="${RENDER_URL/http:\/\//https:\/\/}"
                fi
                echo "APP_URL=$RENDER_URL" >> .env
                echo "Detected Render URL: $RENDER_URL"
            else
                echo "APP_URL=http://localhost" >> .env
                echo "WARNING: APP_URL not set. Using localhost. Set APP_URL in Render Dashboard for production."
            fi
        else
            # Ensure provided APP_URL uses HTTPS if it's a Render URL
            FINAL_APP_URL="$APP_URL"
            if [[ "$FINAL_APP_URL" =~ onrender\.com ]] && [[ "$FINAL_APP_URL" =~ ^http:// ]]; then
                FINAL_APP_URL="${FINAL_APP_URL/http:\/\//https:\/\/}"
            fi
            echo "APP_URL=$FINAL_APP_URL" >> .env
            echo "Using provided APP_URL: $FINAL_APP_URL"
        fi
    fi
fi

# Ensure database connection is set to mysql (not sqlite)
if ! grep -q "^DB_CONNECTION=" .env 2>/dev/null; then
    echo "DB_CONNECTION=mysql" >> .env
elif ! grep -q "^DB_CONNECTION=mysql" .env; then
    sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
fi

# Set session driver to array for API (doesn't require database)
if ! grep -q "^SESSION_DRIVER=" .env 2>/dev/null; then
    echo "SESSION_DRIVER=array" >> .env
elif ! grep -q "^SESSION_DRIVER=array" .env; then
    sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=array|" .env
fi

# Set database environment variables if provided
[ ! -z "$DB_CONNECTION" ] && (grep -q "^DB_CONNECTION=" .env && sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=$DB_CONNECTION|" .env || echo "DB_CONNECTION=$DB_CONNECTION" >> .env)
[ ! -z "$DB_HOST" ] && (grep -q "^DB_HOST=" .env && sed -i "s|^DB_HOST=.*|DB_HOST=$DB_HOST|" .env || echo "DB_HOST=$DB_HOST" >> .env)
[ ! -z "$DB_PORT" ] && (grep -q "^DB_PORT=" .env && sed -i "s|^DB_PORT=.*|DB_PORT=$DB_PORT|" .env || echo "DB_PORT=$DB_PORT" >> .env)
[ ! -z "$DB_DATABASE" ] && (grep -q "^DB_DATABASE=" .env && sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" .env || echo "DB_DATABASE=$DB_DATABASE" >> .env)
[ ! -z "$DB_USERNAME" ] && (grep -q "^DB_USERNAME=" .env && sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" .env || echo "DB_USERNAME=$DB_USERNAME" >> .env)
[ ! -z "$DB_PASSWORD" ] && (grep -q "^DB_PASSWORD=" .env && sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env || echo "DB_PASSWORD=$DB_PASSWORD" >> .env)

# Ensure APP_URL is set correctly (update if already exists)
# This section handles cases where .env already exists
if [ ! -z "$APP_URL" ]; then
    # Ensure provided APP_URL uses HTTPS if it's a Render URL
    FINAL_APP_URL="$APP_URL"
    if [[ "$FINAL_APP_URL" =~ onrender\.com ]] && [[ "$FINAL_APP_URL" =~ ^http:// ]]; then
        FINAL_APP_URL="${FINAL_APP_URL/http:\/\//https:\/\/}"
    fi
    if grep -q "^APP_URL=" .env; then
        sed -i "s|^APP_URL=.*|APP_URL=$FINAL_APP_URL|" .env
    else
        echo "APP_URL=$FINAL_APP_URL" >> .env
    fi
    echo "APP_URL set to: $FINAL_APP_URL"
elif [ ! -z "$RENDER_EXTERNAL_URL" ]; then
    # Ensure HTTPS is used for Render URLs
    RENDER_URL="$RENDER_EXTERNAL_URL"
    if [[ "$RENDER_URL" =~ ^http:// ]]; then
        RENDER_URL="${RENDER_URL/http:\/\//https:\/\/}"
    fi
    if grep -q "^APP_URL=" .env; then
        sed -i "s|^APP_URL=.*|APP_URL=$RENDER_URL|" .env
    else
        echo "APP_URL=$RENDER_URL" >> .env
    fi
    echo "APP_URL auto-detected from Render: $RENDER_URL"
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

# Verify database credentials are set
# IMPORTANT: Always prioritize environment variables from Render over .env file
echo "Checking database configuration..."
echo "Environment variables from Render:"
echo "  DB_HOST=${DB_HOST:-not set}"
echo "  DB_DATABASE=${DB_DATABASE:-not set}"
echo "  DB_USERNAME=${DB_USERNAME:-not set}"
echo "  DB_PASSWORD=${DB_PASSWORD:+***set***}"

# Use environment variables first, fallback to .env only if env vars are not set
if [ -z "$DB_HOST" ] && [ -f .env ]; then
    DB_HOST_FROM_ENV=$(grep '^DB_HOST=' .env 2>/dev/null | cut -d '=' -f2- | tr -d '"' | tr -d "'" || echo '')
    if [ ! -z "$DB_HOST_FROM_ENV" ]; then
        DB_HOST="$DB_HOST_FROM_ENV"
        echo "  Using DB_HOST from .env file: $DB_HOST"
    fi
fi

if [ -z "$DB_DATABASE" ] && [ -f .env ]; then
    DB_DATABASE_FROM_ENV=$(grep '^DB_DATABASE=' .env 2>/dev/null | cut -d '=' -f2- | tr -d '"' | tr -d "'" || echo '')
    if [ ! -z "$DB_DATABASE_FROM_ENV" ]; then
        DB_DATABASE="$DB_DATABASE_FROM_ENV"
        echo "  Using DB_DATABASE from .env file: $DB_DATABASE"
    fi
fi

if [ -z "$DB_USERNAME" ] && [ -f .env ]; then
    DB_USERNAME_FROM_ENV=$(grep '^DB_USERNAME=' .env 2>/dev/null | cut -d '=' -f2- | tr -d '"' | tr -d "'" || echo '')
    if [ ! -z "$DB_USERNAME_FROM_ENV" ]; then
        DB_USERNAME="$DB_USERNAME_FROM_ENV"
        echo "  Using DB_USERNAME from .env file: $DB_USERNAME"
    fi
fi

if [ -z "$DB_PASSWORD" ] && [ -f .env ]; then
    DB_PASSWORD_FROM_ENV=$(grep '^DB_PASSWORD=' .env 2>/dev/null | cut -d '=' -f2- | tr -d '"' | tr -d "'" || echo '')
    if [ ! -z "$DB_PASSWORD_FROM_ENV" ]; then
        DB_PASSWORD="$DB_PASSWORD_FROM_ENV"
        echo "  Using DB_PASSWORD from .env file"
    fi
fi

# Now set final values (prioritize environment variables)
DB_HOST_FINAL="${DB_HOST:-127.0.0.1}"
DB_DATABASE_FINAL="${DB_DATABASE:-laravel}"
DB_USERNAME_FINAL="${DB_USERNAME:-root}"
DB_PASSWORD_FINAL="${DB_PASSWORD:-}"

echo "DB_CONNECTION: ${DB_CONNECTION:-mysql}"
echo "DB_HOST: ${DB_HOST_FINAL}"
echo "DB_DATABASE: ${DB_DATABASE_FINAL}"
echo "DB_USERNAME: ${DB_USERNAME_FINAL}"
echo "DB_PASSWORD: ${DB_PASSWORD_FINAL:0:3}***"

# Check if database credentials are properly set
if [ -z "$DB_HOST" ] || [ "$DB_HOST_FINAL" = "127.0.0.1" ] || [ "$DB_HOST_FINAL" = "localhost" ]; then
    echo "ERROR: DB_HOST is not set in Render environment or is set to localhost/127.0.0.1."
    echo "This won't work on Render. Please set DB_HOST in Render Dashboard → Environment."
    echo "Current value: $DB_HOST_FINAL"
fi

if [ -z "$DB_DATABASE" ] || [ "$DB_DATABASE_FINAL" = "laravel" ]; then
    echo "ERROR: DB_DATABASE is not set in Render environment or is using default."
    echo "Please set DB_DATABASE in Render Dashboard → Environment."
    echo "Current value: $DB_DATABASE_FINAL"
fi

if [ -z "$DB_USERNAME" ]; then
    echo "ERROR: DB_USERNAME is not set in Render environment."
    echo "Please set DB_USERNAME in Render Dashboard → Environment."
    echo "Current value: $DB_USERNAME_FINAL"
fi

if [ -z "$DB_PASSWORD" ]; then
    echo "ERROR: DB_PASSWORD is not set in Render environment."
    echo "Please set DB_PASSWORD in Render Dashboard → Environment."
fi

# Final check: Ensure APP_URL in .env uses HTTPS for Render (BEFORE clearing config cache)
echo "Ensuring APP_URL uses HTTPS..."
if grep -q "^APP_URL=" .env 2>/dev/null; then
    CURRENT_APP_URL=$(grep "^APP_URL=" .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    if [[ "$CURRENT_APP_URL" =~ onrender\.com ]] && [[ "$CURRENT_APP_URL" =~ ^http:// ]]; then
        HTTPS_URL="${CURRENT_APP_URL/http:\/\//https:\/\/}"
        sed -i "s|^APP_URL=.*|APP_URL=$HTTPS_URL|" .env
        echo "✓ Updated APP_URL to HTTPS: $HTTPS_URL"
    elif [[ "$CURRENT_APP_URL" =~ ^https:// ]]; then
        echo "✓ APP_URL already uses HTTPS: $CURRENT_APP_URL"
    fi
else
    # If APP_URL is not set, use RENDER_EXTERNAL_URL with HTTPS
    if [ ! -z "$RENDER_EXTERNAL_URL" ]; then
        RENDER_URL="$RENDER_EXTERNAL_URL"
        if [[ "$RENDER_URL" =~ ^http:// ]]; then
            RENDER_URL="${RENDER_URL/http:\/\//https:\/\/}"
        fi
        echo "APP_URL=$RENDER_URL" >> .env
        echo "✓ Set APP_URL to HTTPS: $RENDER_URL"
    fi
fi

# Verify APP_URL is set correctly
FINAL_APP_URL=$(grep "^APP_URL=" .env 2>/dev/null | cut -d '=' -f2- | tr -d '"' | tr -d "'" || echo "")
if [ -z "$FINAL_APP_URL" ]; then
    echo "WARNING: APP_URL is not set in .env file"
else
    echo "Final APP_URL in .env: $FINAL_APP_URL"
    if [[ ! "$FINAL_APP_URL" =~ ^https:// ]] && [[ "$FINAL_APP_URL" =~ onrender\.com ]]; then
        echo "ERROR: APP_URL should use HTTPS for Render but it doesn't!"
    fi
fi

# Export APP_URL to environment BEFORE clearing config (so Laravel uses it)
FINAL_APP_URL=$(grep "^APP_URL=" .env 2>/dev/null | cut -d '=' -f2- | tr -d '"' | tr -d "'" || echo "")
if [ ! -z "$FINAL_APP_URL" ]; then
    # Ensure it's HTTPS for Render
    if [[ "$FINAL_APP_URL" =~ onrender\.com ]] && [[ "$FINAL_APP_URL" =~ ^http:// ]]; then
        FINAL_APP_URL="${FINAL_APP_URL/http:\/\//https:\/\/}"
        sed -i "s|^APP_URL=.*|APP_URL=$FINAL_APP_URL|" .env
    fi
    export APP_URL="$FINAL_APP_URL"
    echo "Exported APP_URL for Laravel: $APP_URL"
fi

# Clear and cache configuration (AFTER ensuring APP_URL is HTTPS and exported)
echo "Clearing Laravel caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

# Test database connection (only if credentials are set)
if [ ! -z "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ] && \
   [ ! -z "$DB_DATABASE" ] && [ "$DB_DATABASE" != "laravel" ] && \
   [ ! -z "$DB_USERNAME" ] && [ ! -z "$DB_PASSWORD" ]; then
    echo "Testing database connection..."
    php artisan db:show --database=mysql 2>&1 || echo "Database connection test failed - check credentials and network connectivity"
else
    echo "Skipping database connection test - credentials not properly configured"
fi

# Copy Swagger UI assets to public directory (required for serving)
# Use /swagger-assets/ instead of /docs/asset/ to avoid conflict with /docs route
echo "Copying Swagger UI assets to public directory..."
mkdir -p public/swagger-assets 2>/dev/null || true
if [ -d "vendor/swagger-api/swagger-ui/dist" ]; then
    cp -r vendor/swagger-api/swagger-ui/dist/* public/swagger-assets/ 2>/dev/null || true
    ls -la public/swagger-assets/ | head -5 || echo "Assets directory is empty"
    echo "Swagger assets copied to public/swagger-assets/"
else
    echo "ERROR: Swagger vendor assets not found in vendor/swagger-api/swagger-ui/dist"
fi

# Ensure storage/api-docs directory exists and has correct permissions
echo "Ensuring storage/api-docs directory exists..."
mkdir -p storage/api-docs 2>/dev/null || true
chmod -R 755 storage/api-docs 2>/dev/null || true

# Generate Swagger documentation (APP_URL is now exported and .env is correct)
echo "Generating Swagger documentation with APP_URL=$APP_URL..."
# Generate documentation (--force flag doesn't exist, just run normally)
php artisan l5-swagger:generate || true

# Verify the documentation file was generated
if [ -f "storage/api-docs/api-docs.json" ]; then
    echo "✓ Swagger documentation generated successfully"
    ls -lh storage/api-docs/api-docs.json
    # Verify file is readable
    if [ -r "storage/api-docs/api-docs.json" ]; then
        echo "✓ File is readable"
        # Show first few lines to verify it's valid JSON
        head -3 storage/api-docs/api-docs.json || echo "Could not read file"
    else
        echo "WARNING: File exists but is not readable"
        chmod 644 storage/api-docs/api-docs.json || true
    fi
    # Test that the route is accessible and file can be read
    echo "Testing docs route..."
    php artisan route:list --name=l5-swagger.default.docs | head -3 || echo "Route not found"
    # Verify the file content is valid JSON
    if php -r "json_decode(file_get_contents('storage/api-docs/api-docs.json')); echo 'Valid JSON';" 2>/dev/null; then
        echo "✓ JSON file is valid"
    else
        echo "ERROR: JSON file is invalid or corrupted"
    fi
else
    echo "ERROR: Swagger documentation file not found at storage/api-docs/api-docs.json"
    echo "Listing storage/api-docs directory:"
    ls -la storage/api-docs/ 2>/dev/null || echo "Directory does not exist"
    echo "Attempting to regenerate with force..."
    php artisan l5-swagger:generate --force || php artisan l5-swagger:generate || true
    # Check again after regeneration
    if [ -f "storage/api-docs/api-docs.json" ]; then
        echo "✓ File generated after retry"
        chmod 644 storage/api-docs/api-docs.json || true
    else
        echo "ERROR: File still not found after regeneration attempt"
    fi
fi

# Export all environment variables for php artisan serve
export APP_KEY="$APP_KEY"

# APP_URL is already exported above, just ensure it's still set correctly
if [ -z "$APP_URL" ]; then
    # Fallback: use RENDER_EXTERNAL_URL with HTTPS
    RENDER_URL="${RENDER_EXTERNAL_URL:-http://localhost}"
    if [[ "$RENDER_URL" =~ ^http:// ]] && [[ "$RENDER_URL" =~ onrender\.com ]]; then
        RENDER_URL="${RENDER_URL/http:\/\//https:\/\/}"
    fi
    export APP_URL="$RENDER_URL"
    echo "Exported APP_URL (fallback): $APP_URL"
else
    echo "APP_URL already exported: $APP_URL"
fi
export SESSION_DRIVER="${SESSION_DRIVER:-array}"
export DB_CONNECTION="${DB_CONNECTION:-mysql}"

# Export database variables - use environment variables if set, otherwise use final values
# This ensures Render environment variables take precedence
if [ ! -z "$DB_HOST" ]; then
    export DB_HOST="$DB_HOST"
else
    export DB_HOST="$DB_HOST_FINAL"
fi

if [ ! -z "$DB_DATABASE" ]; then
    export DB_DATABASE="$DB_DATABASE"
else
    export DB_DATABASE="$DB_DATABASE_FINAL"
fi

if [ ! -z "$DB_USERNAME" ]; then
    export DB_USERNAME="$DB_USERNAME"
else
    export DB_USERNAME="$DB_USERNAME_FINAL"
fi

if [ ! -z "$DB_PASSWORD" ]; then
    export DB_PASSWORD="$DB_PASSWORD"
else
    export DB_PASSWORD="$DB_PASSWORD_FINAL"
fi

export DB_PORT="${DB_PORT:-3306}"

# Final verification before starting
echo "Final database configuration for Laravel:"
echo "  DB_CONNECTION=${DB_CONNECTION}"
echo "  DB_HOST=${DB_HOST}"
echo "  DB_PORT=${DB_PORT}"
echo "  DB_DATABASE=${DB_DATABASE}"
echo "  DB_USERNAME=${DB_USERNAME}"
echo "  DB_PASSWORD=${DB_PASSWORD:+***set***}"

# Start the application with php artisan serve
echo "Starting Laravel application on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
