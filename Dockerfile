FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer file first
COPY composer.json ./

# Install dependencies without scripts/autoloader first
RUN composer install --no-scripts --no-autoloader --no-dev

# Copy application code
COPY . .

# Complete composer setup
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Generate application key
RUN php artisan key:generate

# Create startup script
RUN echo '#!/bin/bash\nphp artisan migrate --force\nphp artisan serve --host=0.0.0.0 --port=8000' > /start.sh && chmod +x /start.sh

# Expose port 8000 and start php-fpm server
EXPOSE 8000
CMD ["/start.sh"]
