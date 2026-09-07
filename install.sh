#!/bin/bash

# ZKTeco Attendance System Installation Script
# This script helps set up the Laravel ZKTeco attendance system

echo "🚀 ZKTeco Attendance System Installation"
echo "=========================================="

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "📋 Creating .env file from .env.example..."
    cp .env.example .env
else
    echo "✅ .env file already exists"
fi

# Install composer dependencies
echo "📦 Installing Composer dependencies..."
composer install

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate

# Create database if it doesn't exist
echo "🗄️  Setting up database..."
echo "Please make sure your database 'limerick_zkteco' exists in MySQL"
echo "You can create it with: CREATE DATABASE limerick_zkteco;"

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate

# Seed the database
echo "🌱 Seeding database with sample data..."
php artisan db:seed

# Clear cache
echo "🧹 Clearing application cache..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo ""
echo "✅ Installation completed!"
echo ""
echo "📝 Next steps:"
echo "1. Update your .env file with correct database credentials"
echo "2. Configure ZKTeco device settings in .env:"
echo "   - ZKTECO_DEVICE_IP=192.168.1.201"
echo "   - ZKTECO_DEVICE_PORT=4370"
echo "3. Start the development server: php artisan serve"
echo "4. Visit: http://localhost:8000/login"
echo "5. Login with: admin@example.com / password"
echo ""
echo "🔧 For production deployment, remember to:"
echo "- Set APP_ENV=production in .env"
echo "- Set APP_DEBUG=false in .env"
echo "- Configure your web server (Apache/Nginx)"
echo "- Set up SSL certificates"
echo ""
echo "📖 Check README.md for detailed configuration instructions"