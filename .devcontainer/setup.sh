#!/bin/bash
set -e

# Setup script for Copilot coding agent
# This script ensures environment is set up correctly without hitting firewall restrictions

echo "🔧 Setting up User Service development environment..."

# Check if we're in a Docker environment
if [ -f /.dockerenv ]; then
    echo "✅ Running inside Docker container"
    
    # If vendor directory doesn't exist, we need to install dependencies
    if [ ! -d "vendor" ]; then
        echo "📦 Installing PHP dependencies..."
        composer install --no-progress --prefer-dist --optimize-autoloader
    else
        echo "✅ PHP dependencies already installed"
    fi
    
    # Make sure cache and logs directories exist
    mkdir -p var/cache var/log
    
    # Set up database if needed
    if [ -n "${DATABASE_URL:-}" ]; then
        echo "🗄️ Setting up database..."
        php bin/console doctrine:database:create --if-not-exists --no-interaction
        php bin/console doctrine:migrations:migrate --no-interaction
    fi
    
    echo "✅ Environment setup complete!"
else
    echo "🐳 Setting up with Docker..."
    
    # Use make start to set up the entire environment
    # This ensures dependencies are installed in Docker containers before firewall restrictions
    make start
    
    echo "✅ Docker environment started successfully!"
    echo "💡 Use 'make sh' to access the PHP container shell"
fi

echo ""
echo "🚀 Development environment is ready!"
echo "   - Application: http://localhost:8081"
echo "   - Database: localhost:3306"
echo "   - Redis: localhost:6379"
echo "   - MailCatcher: http://localhost:1080"
echo "   - LocalStack: http://localhost:4566"
echo "   - Structurizr: http://localhost:8080"
echo ""
echo "📋 Common commands:"
echo "   make help              - Show all available commands"
echo "   make tests-with-coverage - Run tests"
echo "   make sh                - Access container shell"
echo "   make logs              - Show application logs"