#!/bin/bash

echo "==================================="
echo "Система управления заказами"
echo "==================================="
echo ""

# Проверка Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer не установлен!"
    echo "Установите Composer: brew install composer"
    echo "Или следуйте инструкциям в INSTALLATION.md"
    exit 1
fi

echo "✓ Composer установлен"

# Установка зависимостей
if [ ! -d "vendor" ]; then
    echo ""
    echo "📦 Установка зависимостей..."
    composer install
else
    echo "✓ Зависимости уже установлены"
fi

# Проверка БД
echo ""
echo "📊 Проверка базы данных..."
echo "Убедитесь, что вы создали БД и настроили config/db.php"
echo ""
read -p "Применить миграции? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    chmod +x yii
    php yii migrate --interactive=0
    echo "✓ Миграции применены"
fi

# Запуск сервера
echo ""
echo "🚀 Запуск сервера..."
echo ""
echo "Доступ к системе:"
echo "  - Админ-панель: http://localhost:8080"
echo "  - Логин: admin / admin123 (или manager / manager123)"
echo ""
echo "Для остановки нажмите Ctrl+C"
echo ""

php yii serve --port=8080
