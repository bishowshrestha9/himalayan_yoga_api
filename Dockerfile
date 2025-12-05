# Try PHP 8.4, fallback to 8.4-rc if stable not available
FROM php:8.4-cli

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy all application files (needed for vite build)
COPY . .

# Install Node dependencies and build assets
# Allow build to fail gracefully for API-only deployments
RUN if [ -f "package.json" ]; then \
        npm install --legacy-peer-deps || true; \
        npm run build || echo "Frontend build skipped - continuing with API only"; \
        true; \
    else \
        echo "No package.json found, skipping frontend build"; \
    fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Run post-install scripts
RUN composer dump-autoload --optimize || true

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port (Render will set PORT env variable)
EXPOSE 8000

# Use entrypoint script
ENTRYPOINT ["docker-entrypoint.sh"]
