#!/bin/bash

# Скрипт для полной очистки кеша Yii2

echo "🧹 Очистка кеша Yii2..."

# Очистка assets
echo "📦 Очистка web/assets..."
rm -rf web/assets/*
touch web/assets/.gitkeep

# Очистка runtime cache
echo "💾 Очистка runtime/cache..."
rm -rf runtime/cache/*

# Очистка runtime debug
echo "🐛 Очистка runtime/debug..."
rm -rf runtime/debug/*

# Очистка runtime logs (опционально)
# echo "📝 Очистка runtime/logs..."
# rm -rf runtime/logs/*

echo "✅ Кеш успешно очищен!"
echo "🔄 Обновите страницу в браузере с Ctrl+Shift+R (или Cmd+Shift+R на Mac)"
