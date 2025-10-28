#!/bin/bash

###############################################################################
# Автоматический деплой на zakaz.sneaker-head.by
###############################################################################

set -e  # Остановка при ошибке

echo "════════════════════════════════════════════════════════"
echo "  🚀 ДЕПЛОЙ НА zakaz.sneaker-head.by"
echo "════════════════════════════════════════════════════════"
echo ""

# ═══════════════════════════════════════════════════════════
# НАСТРОЙКИ - ЗАПОЛНИТЕ ПЕРЕД ЗАПУСКОМ
# ═══════════════════════════════════════════════════════════

# SSH данные
SSH_USER="your_username"              # Ваш SSH username
SSH_HOST="sneaker-head.by"            # Хост
SSH_PORT="22"                          # SSH порт (обычно 22)

# Путь на сервере
REMOTE_PATH="/home/username/public_html/zakaz"  # Путь к папке сайта

# GitHub репозиторий
GITHUB_REPO="https://github.com/username/repo.git"  # URL вашего репозитория

# ═══════════════════════════════════════════════════════════
# НЕ ИЗМЕНЯЙТЕ НИЖЕ (если не знаете что делаете)
# ═══════════════════════════════════════════════════════════

echo "📋 Параметры деплоя:"
echo "   SSH: $SSH_USER@$SSH_HOST:$SSH_PORT"
echo "   Путь: $REMOTE_PATH"
echo "   Repo: $GITHUB_REPO"
echo ""

read -p "Продолжить? (y/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Отменено"
    exit 1
fi

echo ""
echo "════════════════════════════════════════════════════════"
echo "  📤 ШАГ 1: Push в GitHub"
echo "════════════════════════════════════════════════════════"

# Проверка изменений
if [[ -n $(git status -s) ]]; then
    echo "📝 Обнаружены изменения. Коммитим..."
    git add .
    read -p "Описание коммита: " COMMIT_MSG
    git commit -m "$COMMIT_MSG"
else
    echo "✅ Изменений нет"
fi

echo "⬆️  Push в GitHub..."
git push origin main

echo "✅ Код загружен в GitHub"
echo ""

echo "════════════════════════════════════════════════════════"
echo "  🖥️  ШАГ 2: Деплой на сервер"
echo "════════════════════════════════════════════════════════"

# Выполнение команд на удаленном сервере
ssh -p $SSH_PORT $SSH_USER@$SSH_HOST << 'ENDSSH'

set -e

echo "📂 Переход в папку проекта..."
cd REMOTE_PATH_PLACEHOLDER

echo "⬇️  Подтягивание изменений из GitHub..."
git pull origin main

echo "📦 Установка зависимостей..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
else
    php composer.phar install --no-dev --optimize-autoloader
fi

echo "🗄️  Проверка миграций..."
php yii migrate --interactive=0

echo "🧹 Очистка кэша..."
rm -rf runtime/cache/*
rm -rf web/assets/*

echo "✅ Деплой завершен!"

ENDSSH

# Замена плейсхолдера на реальный путь
ssh -p $SSH_PORT $SSH_USER@$SSH_HOST "cd $REMOTE_PATH && git pull origin main && composer install --no-dev --optimize-autoloader && php yii migrate --interactive=0 && rm -rf runtime/cache/* && rm -rf web/assets/*"

echo ""
echo "════════════════════════════════════════════════════════"
echo "  ✅ ДЕПЛОЙ УСПЕШНО ЗАВЕРШЕН!"
echo "════════════════════════════════════════════════════════"
echo ""
echo "🌐 Сайт: http://zakaz.sneaker-head.by"
echo ""
echo "📝 Следующие шаги:"
echo "   1. Откройте сайт в браузере"
echo "   2. Проверьте что всё работает"
echo "   3. Войдите в админку"
echo ""
