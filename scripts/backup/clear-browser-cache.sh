#!/bin/bash

# ========================================
# Скрипт для очистки кэша и проверки CSS
# ========================================

echo "🧹 Очистка кэша и проверка премиального burger menu..."
echo ""

# 1. Проверка существующих mobile-menu CSS файлов
echo "📁 Проверка файлов mobile-menu:"
find web/css -name "*mobile-menu*" -type f
echo ""

# 2. Размер premium файла
echo "📊 Размер mobile-menu-premium.css:"
ls -lh web/css/mobile-menu-premium.css | awk '{print $5, $9}'
echo ""

# 3. Версия в AppAsset
echo "🔍 Версия CSS в AppAsset.php:"
grep "mobile-menu-premium.css" assets/AppAsset.php
echo ""

# 4. Проверка ключевых премиальных стилей
echo "✨ Проверка премиальных эффектов в CSS:"
echo "   - Анимации:"
grep -c "@keyframes" web/css/mobile-menu-premium.css | xargs echo "     Всего анимаций:"
grep "menuSlideIn\|headerShine\|textGlow\|buttonShine\|overlayPulse" web/css/mobile-menu-premium.css | wc -l | xargs echo "     Премиальные анимации:"

echo "   - Градиенты:"
grep -c "linear-gradient\|radial-gradient" web/css/mobile-menu-premium.css | xargs echo "     Градиентов:"

echo "   - Свечения (box-shadow):"
grep -c "box-shadow" web/css/mobile-menu-premium.css | xargs echo "     Свечений:"

echo "   - Глянец (inset):"
grep -c "inset" web/css/mobile-menu-premium.css | xargs echo "     Глянцевых эффектов:"
echo ""

# 5. Инструкции для пользователя
echo "📝 Инструкции по очистке кэша:"
echo ""
echo "Chrome/Edge:"
echo "  1. Нажмите Cmd+Shift+Delete (Mac) или Ctrl+Shift+Delete (Windows)"
echo "  2. Выберите 'За все время'"
echo "  3. Отметьте 'Изображения и другие файлы, сохраненные в кэше'"
echo "  4. Нажмите 'Удалить данные'"
echo "  5. Перезагрузите страницу (Cmd+R или Ctrl+R)"
echo ""
echo "Safari:"
echo "  1. Нажмите Cmd+Option+E"
echo "  2. Перезагрузите страницу (Cmd+R)"
echo ""
echo "Firefox:"
echo "  1. Нажмите Cmd+Shift+Delete"
echo "  2. Выберите 'Кэш'"
echo "  3. Нажмите 'Удалить сейчас'"
echo ""
echo "🚀 Или просто нажмите Cmd+Shift+R (жесткая перезагрузка)"
echo ""

# 6. Проверка, что нет конфликтующих файлов
echo "⚠️  Проверка конфликтов:"
if [ -f "web/css/mobile-menu-fullscreen.css" ]; then
    echo "   ❌ НАЙДЕН mobile-menu-fullscreen.css - УДАЛИТЕ ЕГО!"
else
    echo "   ✅ Конфликтующих файлов не найдено"
fi
echo ""

echo "✅ Проверка завершена!"
echo "🔗 Откройте http://localhost:8080/ и нажмите Cmd+Shift+R"
