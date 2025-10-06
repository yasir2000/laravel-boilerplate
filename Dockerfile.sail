# Laravel Sail Alternative for Windows
FROM laravelsail/php83-composer:latest

WORKDIR /var/www/html

# Install additional PHP extensions
RUN apt-get update && apt-get install -y \
    libexif-dev \
    && docker-php-ext-install exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install FrankenPHP
RUN curl -fsSL https://github.com/dunglas/frankenphp/releases/latest/download/frankenphp-linux-x86_64 -o /usr/local/bin/frankenphp \
    && chmod +x /usr/local/bin/frankenphp

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Copy composer files
COPY composer*.json ./

# Install PHP dependencies with platform requirements ignored temporarily
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-scripts --no-autoloader --no-dev --prefer-dist --ignore-platform-reqs

# Copy application
COPY . .

# Generate autoload with superuser permission
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize

# Install Node dependencies and build assets (if package.json exists)
RUN if [ -f "package.json" ]; then npm install && npm run build; fi

# Set permissions - use www-data user instead of sail
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

EXPOSE 80

# Start Laravel with Artisan serve
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]