FROM php:8.2-apache

# Установить системные зависимости
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libxpm-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    g++ \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Настроить и установить GD
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

# Установить расширения по группам (надежнее)
RUN docker-php-ext-install -j$(nproc) gd

# Установить базовые расширения
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_sqlite \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath

# Установить zip
RUN docker-php-ext-install -j$(nproc) zip

# Настроить и установить intl
RUN docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl

# Установить soap и opcache
RUN docker-php-ext-install -j$(nproc) soap opcache

# Увеличить memory_limit для Composer
RUN echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini

# Получить Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установить рабочую директорию
WORKDIR /var/www/html

# Скопировать composer файлы сначала (для кеширования слоя)
COPY composer.json composer.lock* /var/www/html/

# Установить зависимости с расширенными настройками
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --verbose \
    || composer install --no-dev --no-interaction --prefer-dist

# Скопировать остальные файлы проекта
COPY . /var/www/html

# Выполнить post-install скрипты
RUN composer dump-autoload --optimize --no-dev

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
