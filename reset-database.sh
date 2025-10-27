#!/bin/bash

# Database Reset Script for The Last Messenger
# This script safely resets the database and runs fresh migrations

echo "🗄️  The Last Messenger - Database Reset"
echo "========================================"
echo ""

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    exit 1
fi

# Get database connection from .env
DB_CONNECTION=$(grep "^DB_CONNECTION=" .env | cut -d '=' -f2)

echo "📋 Current database: $DB_CONNECTION"
echo ""

# Confirm action
read -p "⚠️  This will DROP ALL TABLES. Continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "❌ Operation cancelled."
    exit 0
fi

echo ""
echo "🔄 Starting database reset..."
echo ""

# Run fresh migration
php artisan migrate:fresh

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Database reset successful!"
    echo ""
    
    # Ask if user wants to seed data
    read -p "📦 Do you want to seed roles and permissions? (yes/no): " seed_confirm
    
    if [ "$seed_confirm" == "yes" ]; then
        echo ""
        echo "🌱 Seeding database..."
        php artisan db:seed --class=RolePermissionSeeder
        
        if [ $? -eq 0 ]; then
            echo "✅ Seeding completed!"
        else
            echo "❌ Seeding failed!"
        fi
    fi
    
    echo ""
    echo "📊 Migration status:"
    php artisan migrate:status
    
    echo ""
    echo "🎉 All done! Your database is ready."
else
    echo ""
    echo "❌ Migration failed! Please check the error above."
    exit 1
fi

