# Use PHP-FPM for better static file serving
FROM php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies including nginx
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
    nginx \
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
# Create build directory first to ensure it exists
RUN mkdir -p /var/www/html/public/build/assets || true

# Install and build
RUN if [ -f "package.json" ]; then \
        echo "Installing npm dependencies..." && \
        npm install --legacy-peer-deps 2>&1 | head -20 || echo "npm install had warnings"; \
        echo "Building assets..." && \
        npm run build 2>&1 || echo "Build failed - assets may not be available"; \
        ls -la /var/www/html/public/build/ 2>/dev/null || echo "Build directory not created"; \
    else \
        echo "No package.json found, skipping frontend build"; \
    fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/public/build 2>/dev/null || true

# Run post-install scripts
RUN composer dump-autoload --optimize || true

# Configure nginx
RUN echo 'server { \
    listen 8000; \
    server_name _; \
    root /var/www/html/public; \
    index index.php index.html; \
    \
    # Deny access to hidden files \
    location ~ /\.(?!well-known).* { \
        deny all; \
    } \
    \
    # Route Swagger asset requests directly through Laravel (highest priority) \
    location ~ ^/docs/asset/ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME $document_root/index.php; \
        fastcgi_param REQUEST_URI $request_uri; \
        fastcgi_param QUERY_STRING $query_string; \
        include fastcgi_params; \
    } \
    \
    # Route API documentation through Laravel \
    location ~ ^/api/documentation { \
        try_files $uri /index.php?$query_string; \
    } \
    \
    # Serve Vite build assets as static files (CSS, JS, images) \
    location ~ ^/build/ { \
        try_files $uri =404; \
        expires 1y; \
        add_header Cache-Control "public, immutable"; \
    } \
    \
    # PHP handler - must come before general location \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        include fastcgi_params; \
    } \
    \
    # Serve static files if they exist, otherwise route to Laravel \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
}' > /etc/nginx/sites-available/default

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port (Render will set PORT env variable)
EXPOSE 8000

# Use entrypoint script
ENTRYPOINT ["docker-entrypoint.sh"]
