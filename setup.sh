#!/bin/bash

# Laravel Boilerplate Setup Script
echo "🚀 Setting up Laravel Boilerplate..."

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP 8.2+ first."
    exit 1
fi

# Install dependencies
echo "📦 Installing PHP dependencies..."
composer install

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating environment file..."
    cp .env.example .env
    echo "✅ Please configure your .env file with your database credentials"
fi

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate

# Check if database connection works
echo "🔍 Checking database connection..."
if php artisan migrate:status &> /dev/null; then
    echo "✅ Database connection successful"
    
    # Ask if user wants to run migrations
    echo "🗃️  Do you want to run database migrations and seeders? (y/N)"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        echo "🏗️  Running migrations..."
        php artisan migrate
        
        echo "🌱 Running seeders..."
        php artisan db:seed
        
        echo "✅ Database setup completed!"
        echo ""
        echo "🎉 Setup completed! Default credentials:"
        echo "   Super Admin: superadmin@laravel-boilerplate.com / password"
        echo "   Company Admin: admin@techsolutions.com / password"
        echo ""
    fi
else
    echo "❌ Database connection failed. Please check your .env configuration."
    echo "   Make sure PostgreSQL is running and credentials are correct."
fi

# Ask if user wants to start the server
echo "🚀 Do you want to start the development server? (y/N)"
read -r response
if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo "🌐 Starting Laravel development server..."
    echo "   Application will be available at: http://localhost:8000"
    echo "   API Health Check: http://localhost:8000/api/health"
    echo ""
    php artisan serve
fi

echo "✨ Setup completed! Happy coding!"
