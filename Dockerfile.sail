# Laravel Sail Alternative for Windows
FROM laravelsail/php83-composer:latest

WORKDIR /var/www/html

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Copy composer files
COPY composer*.json ./

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader --no-dev --prefer-dist

# Copy application
COPY . .

# Generate autoload
RUN composer dump-autoload --optimize

# Install Node dependencies and build assets
RUN npm install && npm run build

# Set permissions
RUN chown -R sail:sail /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

USER sail

EXPOSE 80

# Start with supervisor (like Sail)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]