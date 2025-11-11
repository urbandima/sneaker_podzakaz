#!/bin/bash
# Скрипт для архивации устаревшей документации
# Дата: 9 ноября 2025

echo "🗂️  Начинаем архивацию устаревшей документации..."

# Создать директорию архива
mkdir -p docs/archive

# Счетчик перемещенных файлов
count=0

# Функция для перемещения файла
move_file() {
    if [ -f "$1" ]; then
        mv "$1" docs/archive/
        echo "✅ Перемещен: $1"
        ((count++))
    fi
}

echo ""
echo "📦 Перемещение CATALOG_* файлов..."
move_file "CATALOG_ADAPTIVE_FIX.md"
move_file "CATALOG_CSS_CLEANUP.md"
move_file "FIX_CATALOG_NOW.md"
move_file "FULLWIDTH_CATALOG_OPTIMIZATION.md"
move_file "DESKTOP_CATALOG_OPTIMIZATION.md"

echo ""
echo "📦 Перемещение TRAILING_SLASH_* файлов..."
move_file "TRAILING_SLASH_FIX.md"
move_file "TRAILING_SLASH_SUMMARY.md"
move_file "TRAILING_SLASH_TESTING.md"

echo ""
echo "📦 Перемещение CACHE_* файлов..."
move_file "CACHE_BUSTING_FIX.md"
move_file "CACHE_CLEAR_GUIDE.md"
move_file "CACHE_CLEAR_INSTRUCTIONS.md"

echo ""
echo "📦 Перемещение QUICK_* файлов..."
move_file "QUICK_FIX_GUIDE.md"
move_file "QUICK_OPTIMIZATION_GUIDE.md"
move_file "QUICK_PRICE_GUIDE.md"
move_file "QUICK_WINS_IMPLEMENTATION.md"

echo ""
echo "📦 Перемещение DESKTOP_* файлов..."
move_file "DESKTOP_OPTIMIZATION_SUMMARY.md"
move_file "DESKTOP_TESTING_GUIDE.md"

echo ""
echo "📦 Перемещение других устаревших файлов из корня..."
move_file "CLEANUP_REPORT.md"
move_file "CODEBASE_CLEANUP_REPORT.md"
move_file "STICKY_PANEL_PREMIUM.md"
move_file "TESTING_CHECKLIST.md"
move_file "PRICE_UPDATE_GUIDE.md"
move_file "PERFORMANCE_OPTIMIZATION_PLAN_2025.md"
move_file "PERFORMANCE_OPTIMIZATION_REPORT.md"
move_file "ВЕРСТКА_ПРОБЛЕМЫ.md"

echo ""
echo "📦 Перемещение устаревших файлов из docs/..."
move_file "docs/CATALOG_CHECKLIST_STATUS.md"
move_file "docs/CATALOG_COMPLETE_SUCCESS.md"
move_file "docs/CATALOG_FINAL_REPORT.md"
move_file "docs/CATALOG_GRID_FIX_FINAL.md"
move_file "docs/CATALOG_IMPLEMENTATION_REPORT.md"
move_file "docs/CATALOG_LAYOUT_FIX_2025.md"
move_file "docs/CATALOG_PERFORMANCE_COMPLETE.md"
move_file "docs/CATALOG_QUICK_START.md"
move_file "docs/CATALOG_TESTING_CHECKLIST.md"
move_file "docs/CATALOG_TEST_AND_RUN.md"
move_file "docs/CATALOG_VS_BITRIX24.md"
move_file "docs/ADAPTIVE_TESTING_GUIDE.md"
move_file "docs/PRODUCT_ADAPTIVE_COMPLETE.md"
move_file "docs/SMART_FILTER_BEST_PRACTICES.md"
move_file "docs/SMART_FILTER_FINAL_VERDICT.md"

# CATALOG_ARCHITECTURE.md оставляем, но можно переместить если нужно
# move_file "docs/CATALOG_ARCHITECTURE.md"

echo ""
echo "✅ Архивация завершена!"
echo "📊 Перемещено файлов: $count"
echo "📂 Файлы находятся в: docs/archive/"
echo ""
echo "🔍 Проверить результат:"
echo "   ls -la docs/archive/"
echo ""
echo "📝 Следующие шаги:"
echo "   1. Проверьте содержимое docs/archive/"
echo "   2. Убедитесь, что все нужные файлы сохранены"
echo "   3. Commit изменений: git add . && git commit -m 'docs: архивация устаревшей документации'"
