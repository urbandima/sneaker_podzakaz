#!/bin/bash
set -e

# Применить миграции при первом запуске
if [ ! -f /var/www/html/runtime/database.sqlite ]; then
    echo "Creating database..."
    php /var/www/html/yii migrate --interactive=0
fi

# Заменить порт в конфигурации Apache
if [ -n "$PORT" ]; then
    echo "Configuring Apache to listen on port $PORT"
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
    sed -i "s/*:80/*:$PORT/g" /etc/apache2/sites-available/000-default.conf
fi

# Запустить Apache
echo "Starting Apache..."
apache2-foreground
