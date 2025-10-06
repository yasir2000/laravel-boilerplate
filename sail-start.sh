#!/bin/bash
# Quick Laravel Sail start script

echo "🚀 Starting Laravel Sail..."

# Set environment variables
export WWWUSER=1000
export WWWGROUP=1000

# Start Sail
./vendor/bin/sail up -d

echo "✅ Laravel Sail running!"
echo "🌐 App: http://localhost"
echo "📧 Mailpit: http://localhost:8025"
echo "🔧 Commands:"
echo "  ./vendor/bin/sail artisan octane:install --server=frankenphp"
echo "  ./vendor/bin/sail artisan octane:start"