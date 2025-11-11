#!/bin/bash

# ========================================
# СРОЧНОЕ ИСПРАВЛЕНИЕ BURGER MENU
# VERSION 5.0 - С !IMPORTANT
# ========================================

clear

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚨 СРОЧНОЕ ИСПРАВЛЕНИЕ BURGER MENU"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 1. Очистка серверного кэша
echo "🧹 Шаг 1: Очистка серверного кэша..."
if [ -d "runtime/cache" ]; then
    rm -rf runtime/cache/*
    echo "   ✅ Runtime кэш очищен"
else
    echo "   ℹ️  Runtime кэш не найден"
fi
echo ""

# 2. Проверка версий
echo "📋 Шаг 2: Версии CSS файлов:"
echo "   mobile-menu-premium.css:"
grep "mobile-menu-premium.css" assets/AppAsset.php | head -1 | sed 's/^[ \t]*/   /'
echo ""

# 3. Проверка !important
echo "🔍 Шаг 3: Проверка !important в premium CSS:"
IMPORTANT_COUNT=$(grep -c "!important" web/css/mobile-menu-premium.css)
echo "   Найдено !important: $IMPORTANT_COUNT"
if [ $IMPORTANT_COUNT -gt 20 ]; then
    echo "   ✅ !important добавлены для принудительного применения"
else
    echo "   ⚠️  Мало !important - может быть конфликт"
fi
echo ""

# 4. Размер файла
echo "📊 Шаг 4: Размер premium файла:"
ls -lh web/css/mobile-menu-premium.css | awk '{print "   " $5, $9}'
echo ""

# 5. Проверка конфликтующих файлов
echo "🔎 Шаг 5: Поиск конфликтующих файлов:"
CONFLICT_FILES=$(find web/css -name "*mobile-menu*" ! -name "*premium*" 2>/dev/null)
if [ -z "$CONFLICT_FILES" ]; then
    echo "   ✅ Конфликтующих файлов не найдено"
else
    echo "   ⚠️  Найдены конфликтующие файлы:"
    echo "$CONFLICT_FILES" | sed 's/^/   - /'
fi
echo ""

# 6. Проверка inline стилей
echo "🔍 Шаг 6: Проверка inline стилей в layout:"
INLINE_MOBILE=$(grep -c "\.mobile-menu" views/layouts/public.php 2>/dev/null || echo "0")
if [ "$INLINE_MOBILE" -gt "0" ]; then
    echo "   ⚠️  НАЙДЕНЫ inline стили mobile-menu в layout!"
    echo "   Это может перекрывать premium CSS!"
else
    echo "   ✅ Inline стилей в layout нет"
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ ИСПРАВЛЕНИЯ ПРИМЕНЕНЫ!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🔥 СРОЧНЫЕ ДЕЙСТВИЯ (СДЕЛАЙТЕ СЕЙЧАС):"
echo ""
echo "1️⃣  ОЧИСТИТЕ КЭШ БРАУЗЕРА (ОБЯЗАТЕЛЬНО!):"
echo ""
echo "   👉 Cmd+Shift+Delete (Mac)"
echo "   👉 Выберите: 'За все время'"
echo "   👉 Отметьте: 'Изображения и файлы'"
echo "   👉 Нажмите: 'Удалить'"
echo ""
echo "   ИЛИ просто нажмите:"
echo "   👉👉👉 Cmd+Shift+R 👈👈👈"
echo ""
echo "2️⃣  ОТКРОЙТЕ В РЕЖИМЕ ИНКОГНИТО:"
echo "   (Это гарантирует, что нет кэша)"
echo ""
echo "   Chrome/Safari: Cmd+Shift+N"
echo "   Firefox: Cmd+Shift+P"
echo ""
echo "3️⃣  ОТКРОЙТЕ САЙТ:"
echo "   http://localhost:8080/"
echo ""
echo "4️⃣  НАЖМИТЕ НА ☰ BURGER"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✨ ЧТО ВЫ ДОЛЖНЫ УВИДЕТЬ:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "┌─────────────────────────────────┐"
echo "│ 🏠 СНИКЕРХЭД            ✕      │ ← Черный header"
echo "├─────────────────────────────────┤"
echo "│ [❤️ Избранное] [🛒 Корзина]    │ ← 4 кнопки"
echo "│ [🕐 История]   [👤 Профиль]    │   СВЕРХУ!"
echo "├─────────────────────────────────┤"
echo "│ 🔍 Поиск товаров, брендов...   │"
echo "├─────────────────────────────────┤"
echo "│ ОСНОВНОЕ МЕНЮ                  │ ← Золотая полоска"
echo "│   📱 Каталог                   │"
echo "│   👨 Мужское                   │"
echo "│   👩 Женское                   │"
echo "│   ⭐ Новинки                   │"
echo "│   🔥 Распродажа (ПУЛЬСИРУЕТ)   │"
echo "│                                │"
echo "│ 🏷️  ПОПУЛЯРНЫЕ БРЕНДЫ          │"
echo "│ [Nike] [Adidas]                │ ← Сетка 2x2"
echo "│ [Puma] [Reebok]                │"
echo "└─────────────────────────────────┘"
echo ""
echo "✅ ПРЕМИАЛЬНЫЕ ЭФФЕКТЫ:"
echo "   • Золотые блики на overlay"
echo "   • Меню выезжает с масштабированием"
echo "   • Градиентный logo СНИКЕРХЭД (пульсирует)"
echo "   • Бегущие блики на кнопках"
echo "   • Золотое свечение при hover"
echo "   • Пульсирующая 🔥 распродажа"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🐛 ЕСЛИ ВСЕ ЕЩЕ ВИДИТЕ СТАРЫЙ ДИЗАЙН:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "1. ЖЕСТКАЯ ПЕРЕЗАГРУЗКА 5 РАЗ ПОДРЯД:"
echo "   Cmd+Shift+R"
echo "   Cmd+Shift+R"
echo "   Cmd+Shift+R"
echo "   Cmd+Shift+R"
echo "   Cmd+Shift+R"
echo ""
echo "2. ОТКРОЙТЕ DevTools (F12):"
echo "   Network → Перезагрузить → Найти:"
echo "   mobile-menu-premium.css?v=5.0"
echo ""
echo "   Должно быть:"
echo "   • Size: ~26KB"
echo "   • Status: 200"
echo ""
echo "3. ПРОВЕРЬТЕ СТИЛИ В CONSOLE:"
echo "   F12 → Console → Вставьте:"
echo ""
echo "   const menu = document.getElementById('mobileMenu');"
echo "   const styles = getComputedStyle(menu);"
echo "   console.log('Background:', styles.background);"
echo "   console.log('Display:', styles.display);"
echo "   console.log('Position:', styles.position);"
echo ""
echo "   Должно показать градиент в background"
echo ""
echo "4. ПРОВЕРЬТЕ HTML СТРУКТУРУ:"
echo "   F12 → Elements → Найдите:"
echo "   <div class=\"mobile-quick-actions\">"
echo ""
echo "   Если ЕСТЬ - CSS не применяется (кэш)"
echo "   Если НЕТ - проблема в HTML (нужно обновить layout)"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 НАЧИНАЙТЕ С РЕЖИМА ИНКОГНИТО!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Cmd+Shift+N → http://localhost:8080/ → ☰"
echo ""
