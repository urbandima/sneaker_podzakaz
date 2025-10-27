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

# Настройка PHP для production
COPY php-production.ini /usr/local/etc/php/conf.d/99-production.ini

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

# Заменить index.php на production версию
RUN cp /var/www/html/web/index-prod.php /var/www/html/web/index.php

# Выполнить post-install скрипты
RUN composer dump-autoload --optimize --no-dev

# Создать директории и установить права
RUN mkdir -p runtime web/assets web/uploads \
    && chmod -R 777 runtime web/assets web/uploads \
    && chown -R www-data:www-data /var/www/html

# Настроить Apache для Yii2
RUN a2enmod rewrite

# Скопировать конфигурацию Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Скопировать entrypoint скрипт
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Установить переменные окружения для production
ENV YII_ENV=prod
ENV YII_DEBUG=false

# Открыть порт
EXPOSE ${PORT:-80}

# Запуск
CMD ["/usr/local/bin/docker-entrypoint.sh"]
