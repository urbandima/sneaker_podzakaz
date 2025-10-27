FROM php:8.2-apache

# Установить системные зависимости
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev

# Очистить кеш
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Установить PHP расширения
RUN docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd

# Получить Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установить рабочую директорию
WORKDIR /var/www/html

# Скопировать файлы проекта
COPY . /var/www/html

# Установить зависимости
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Создать директории и установить права
RUN mkdir -p runtime web/assets web/uploads \
    && chmod -R 777 runtime web/assets web/uploads \
    && chown -R www-data:www-data /var/www/html

# Настроить Apache для Yii2
RUN a2enmod rewrite

# Создать конфигурацию Apache
RUN echo '<VirtualHost *:${PORT}>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/web\n\
    \n\
    <Directory /var/www/html/web>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        \n\
        RewriteEngine on\n\
        RewriteCond %{REQUEST_FILENAME} !-f\n\
        RewriteCond %{REQUEST_FILENAME} !-d\n\
        RewriteRule . index.php\n\
    </Directory>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Настроить Apache для работы на динамическом порту
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf

# Создать entrypoint скрипт
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Применить миграции при первом запуске\n\
if [ ! -f /var/www/html/runtime/database.sqlite ]; then\n\
    echo "Creating database..."\n\
    php /var/www/html/yii migrate --interactive=0\n\
fi\n\
\n\
# Обновить конфигурацию Apache с правильным портом\n\
sed -i "s/\${PORT}/$PORT/g" /etc/apache2/sites-available/000-default.conf\n\
sed -i "s/\${PORT}/$PORT/g" /etc/apache2/ports.conf\n\
\n\
# Запустить Apache\n\
apache2-foreground' > /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Открыть порт
EXPOSE ${PORT:-80}

# Запуск
CMD ["/usr/local/bin/docker-entrypoint.sh"]
