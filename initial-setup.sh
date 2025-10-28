#!/bin/bash

###############################################################################
# ПЕРВИЧНАЯ УСТАНОВКА НА zakaz.sneaker-head.by
# Запустить ОДИН РАЗ на сервере после клонирования из GitHub
###############################################################################

set -e

echo "════════════════════════════════════════════════════════"
echo "  🚀 ПЕРВИЧНАЯ УСТАНОВКА zakaz.sneaker-head.by"
echo "════════════════════════════════════════════════════════"
echo ""

# Проверка что мы в правильной директории
if [ ! -f "composer.json" ]; then
    echo "❌ Ошибка: запустите скрипт из корневой папки проекта"
    exit 1
fi

echo "📂 Текущая директория: $(pwd)"
echo ""

# ═══════════════════════════════════════════════════════════
echo "🔧 ШАГ 1: Создание config/db.php"
echo "════════════════════════════════════════════════════════"

if [ -f "config/db.php" ]; then
    echo "⚠️  config/db.php уже существует. Пропускаем..."
else
    if [ -f "config/db-production.php" ]; then
        cp config/db-production.php config/db.php
        echo "✅ config/db.php создан из db-production.php"
    else
        echo "⚠️  db-production.php не найден, создаём вручную..."
        cat > config/db.php << 'EOF'
<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=sneakerh_username_order_management',
    'username' => 'sneakerh_username_order_user',
    'password' => 'kefir1kefir',
    'charset' => 'utf8mb4',
];
EOF
        echo "✅ config/db.php создан"
    fi
fi

echo ""

# ═══════════════════════════════════════════════════════════
echo "🔑 ШАГ 2: Генерация cookieValidationKey"
echo "════════════════════════════════════════════════════════"

NEW_KEY=$(php -r "echo bin2hex(random_bytes(32));")
echo "Сгенерирован ключ: $NEW_KEY"

# Заменяем в config/web.php
sed -i.bak "s/'cookieValidationKey' => '[^']*'/'cookieValidationKey' => '$NEW_KEY'/" config/web.php
rm -f config/web.php.bak

echo "✅ cookieValidationKey обновлен"
echo ""

# ═══════════════════════════════════════════════════════════
echo "📦 ШАГ 3: Установка зависимостей Composer"
echo "════════════════════════════════════════════════════════"

if command -v composer &> /dev/null; then
    echo "Используем системный composer..."
    composer install --no-dev --optimize-autoloader
elif [ -f "composer.phar" ]; then
    echo "Используем локальный composer.phar..."
    php composer.phar install --no-dev --optimize-autoloader
else
    echo "⬇️  Скачиваем composer..."
    curl -sS https://getcomposer.org/installer | php
    php composer.phar install --no-dev --optimize-autoloader
fi

echo "✅ Зависимости установлены"
echo ""

# ═══════════════════════════════════════════════════════════
echo "📁 ШАГ 4: Создание необходимых папок"
echo "════════════════════════════════════════════════════════"

mkdir -p runtime/logs
mkdir -p web/uploads/payments
mkdir -p web/assets

chmod 755 runtime
chmod 755 web/assets
chmod 755 web/uploads
chmod 755 web/uploads/payments

echo "✅ Папки созданы с правильными правами"
echo ""

# ═══════════════════════════════════════════════════════════
echo "🗄️  ШАГ 5: Запуск миграций базы данных"
echo "════════════════════════════════════════════════════════"

php yii migrate --interactive=0

echo "✅ Миграции выполнены"
echo ""

# ═══════════════════════════════════════════════════════════
echo "👤 ШАГ 6: Создание администратора"
echo "════════════════════════════════════════════════════════"

read -p "Создать администратора? (y/n): " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    read -p "Username администратора [admin]: " ADMIN_USER
    ADMIN_USER=${ADMIN_USER:-admin}
    
    read -sp "Password администратора: " ADMIN_PASS
    echo ""
    
    if [ -z "$ADMIN_PASS" ]; then
        echo "❌ Пароль не может быть пустым"
    else
        # Генерируем хеш пароля
        PASS_HASH=$(php -r "echo password_hash('$ADMIN_PASS', PASSWORD_DEFAULT);")
        AUTH_KEY=$(php -r "echo bin2hex(random_bytes(16));")
        
        # SQL запрос
        mysql -h localhost -u sneakerh_username_order_user -pkefir1kefir sneakerh_username_order_management << EOF
INSERT INTO user (username, password_hash, auth_key, role, status, created_at, updated_at)
VALUES ('$ADMIN_USER', '$PASS_HASH', '$AUTH_KEY', 'admin', 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE password_hash='$PASS_HASH';
EOF
        
        echo "✅ Администратор '$ADMIN_USER' создан"
    fi
else
    echo "⏭️  Пропускаем создание администратора"
fi

echo ""

# ═══════════════════════════════════════════════════════════
echo "🧹 ШАГ 7: Очистка временных файлов"
echo "════════════════════════════════════════════════════════"

rm -rf runtime/cache/*
rm -rf web/assets/*

echo "✅ Кэш очищен"
echo ""

# ═══════════════════════════════════════════════════════════
echo "════════════════════════════════════════════════════════"
echo "  ✅ УСТАНОВКА ЗАВЕРШЕНА!"
echo "════════════════════════════════════════════════════════"
echo ""
echo "🌐 Сайт: http://zakaz.sneaker-head.by"
echo "🔐 Админка: http://zakaz.sneaker-head.by/login"
echo ""
echo "📝 Следующие шаги:"
echo "   1. Проверьте что DocumentRoot в cPanel указывает на:"
echo "      /home/sneakerh/zakaz.sneaker-head.by/web"
echo ""
echo "   2. Откройте сайт в браузере"
echo ""
echo "   3. Войдите в админку с созданными учетными данными"
echo ""
echo "🔄 Для обновления в будущем используйте:"
echo "   ./update.sh"
echo ""
