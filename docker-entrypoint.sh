#!/bin/bash
set -e

echo "========================================="
echo "Starting Sneaker Zakaz Application"
echo "========================================="

# Явно установить production переменные окружения
export YII_ENV=prod
export YII_DEBUG=false
export RENDER=true

echo "Environment: YII_ENV=$YII_ENV, YII_DEBUG=$YII_DEBUG"

# Создать runtime директорию если не существует
mkdir -p /var/www/html/runtime /var/www/html/web/assets /var/www/html/web/uploads

# Установить права
chmod -R 777 /var/www/html/runtime /var/www/html/web/assets /var/www/html/web/uploads

# Заменить порт в конфигурации Apache
if [ -n "$PORT" ]; then
    echo "Configuring Apache to listen on port $PORT"
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
    sed -i "s/*:80/*:$PORT/g" /etc/apache2/sites-available/000-default.conf
else
    echo "Using default port 80"
fi

# Проверить наличие базы данных
if [ -f /var/www/html/runtime/database.sqlite ]; then
    echo "Database exists: $(ls -lh /var/www/html/runtime/database.sqlite)"
else
    echo "WARNING: Database not found. You may need to run migrations manually."
    echo "To create database, run: php yii migrate --interactive=0"
fi

# Запустить Apache
echo "Starting Apache web server..."
echo "========================================="
apache2-foreground
