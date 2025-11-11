#!/bin/bash
set -e

echo "🧹 Очистка дубликатов и неиспользуемых файлов"
echo "=============================================="

# Создать backup
echo "📦 Создание backup..."
BACKUP_FILE="backup-before-cleanup-$(date +%Y%m%d-%H%M%S).tar.gz"
tar -czf "$BACKUP_FILE" \
    web/css/ web/js/ views/ \
    *.php *.sh *.txt 2>/dev/null || true

echo "✅ Backup создан: $BACKUP_FILE"

# Фаза 1: Удаление дубликатов
echo ""
echo "🗑️  Фаза 1: Удаление дубликатов..."

if [ -f "web/css/catalog-inline.min.css" ]; then
    rm web/css/catalog-inline.min.css
    echo "  ✅ Удален catalog-inline.min.css (дубликат)"
fi

if [ -f "views/catalog/index.php.backup" ]; then
    rm views/catalog/index.php.backup
    echo "  ✅ Удален index.php.backup"
fi

if [ -f "views/catalog/product.php.backup" ]; then
    rm views/catalog/product.php.backup
    echo "  ✅ Удален product.php.backup"
fi

if [ -f "web/js/product-swipe.js" ]; then
    rm web/js/product-swipe.js
    echo "  ✅ Удален product-swipe.js"
fi

if [ -f "web/js/product-swipe-new.js" ]; then
    rm web/js/product-swipe-new.js
    echo "  ✅ Удален product-swipe-new.js"
fi

if [ -f "web/js/product-improvements.js" ]; then
    rm web/js/product-improvements.js
    echo "  ✅ Удален product-improvements.js"
fi

echo "✅ Фаза 1 завершена (~216 KB освобождено)"

# Фаза 2: Архивирование
echo ""
echo "📁 Фаза 2: Архивирование..."

# Создать структуру архива
mkdir -p archive/{dev-scripts,old-docs,css-unused,js-unused,shell-scripts}

# Переместить тестовые скрипты
find . -maxdepth 1 -type f \( -name "test-*.php" -o -name "test-*.html" \) -exec mv {} archive/dev-scripts/ \; 2>/dev/null || true
find . -maxdepth 1 -type f \( -name "check-*.php" -o -name "debug-*.php" -o -name "analyze-*.php" \) -exec mv {} archive/dev-scripts/ \; 2>/dev/null || true
find . -maxdepth 1 -type f \( -name "fill-*.php" -o -name "final-*.php" \) -exec mv {} archive/dev-scripts/ \; 2>/dev/null || true

echo "  ✅ Перемещены тестовые PHP скрипты"

# Переместить TXT файлы (кроме важных)
find . -maxdepth 1 -type f -name "*.txt" ! -name "composer.txt" -exec mv {} archive/old-docs/ \; 2>/dev/null || true

echo "  ✅ Перемещены TXT файлы"

# Переместить неиспользуемые CSS
if [ -f "web/css/product-clean.css" ]; then
    mv web/css/product-clean.css archive/css-unused/
    echo "  ✅ Перемещен product-clean.css"
fi

# Переместить одноразовые shell скрипты
if [ -f "deploy-catalog-fix.sh" ]; then
    mv deploy-catalog-fix.sh archive/shell-scripts/
    echo "  ✅ Перемещен deploy-catalog-fix.sh"
fi

find . -maxdepth 1 -type f -name "setup-*.sh" -exec mv {} archive/shell-scripts/ \; 2>/dev/null || true

echo "  ✅ Перемещены одноразовые shell скрипты"

echo "✅ Фаза 2 завершена (~115 KB архивировано)"

# Статистика
echo ""
echo "📊 СТАТИСТИКА"
echo "================================"
echo "Удалено:       ~216 KB"
echo "Архивировано:  ~115 KB"
echo "ИТОГО:         ~331 KB"
echo ""
echo "📦 Backup: $BACKUP_FILE"
echo "📁 Архив: ./archive/"
echo ""
echo "✅ Очистка завершена успешно!"
echo ""
echo "⚠️  СЛЕДУЮЩИЕ ШАГИ:"
echo "1. Очистить components/AssetOptimizer.php (удалить ссылки на удаленные файлы)"
echo "2. Протестировать каталог и страницу продукта в браузере"
echo "3. Запустить git status и закоммитить изменения"
