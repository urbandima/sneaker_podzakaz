#!/bin/bash

# Скрипт для очистки данных Poizon и переимпорта

echo "🗑️  Очищаю данные Poizon..."

mysql -u root order_management << 'EOF'
-- Удаляем характеристики
DELETE FROM product_characteristic WHERE product_id IN (SELECT id FROM product WHERE poizon_id IS NOT NULL);

-- Удаляем размеры
DELETE FROM product_size WHERE product_id IN (SELECT id FROM product WHERE poizon_id IS NOT NULL);

-- Удаляем изображения
DELETE FROM product_image WHERE product_id IN (SELECT id FROM product WHERE poizon_id IS NOT NULL);

-- Удаляем товары
DELETE FROM product WHERE poizon_id IS NOT NULL;

-- Проверяем
SELECT 'Осталось товаров Poizon:' as status, COUNT(*) as count FROM product WHERE poizon_id IS NOT NULL;
SELECT 'Осталось размеров:' as status, COUNT(*) as count FROM product_size;
SELECT 'Осталось характеристик:' as status, COUNT(*) as count FROM product_characteristic;
SELECT 'Осталось изображений Poizon:' as status, COUNT(*) as count FROM product_image;
EOF

echo ""
echo "✅ Данные очищены!"
echo ""
echo "🚀 Запускаю импорт..."
echo ""

cd /Users/user/CascadeProjects/splitwise
php yii poizon-import-json/run "https://storage.yandexcloud.net/poizon-exports/site/81988f42-8231-43d7-b4ed-bd70ddf56f06/c8cb6cd8-8970-4b01-976f-9407dea04f9c/export_04_11_2025__15_32_15.json" --verbose=1

echo ""
echo "✅ Импорт завершен!"
echo ""
echo "📊 Проверяю результаты..."
echo ""

mysql -u root order_management << 'EOF'
SELECT 'Товаров:' as metric, COUNT(*) as count FROM product WHERE poizon_id IS NOT NULL AND parent_product_id IS NULL
UNION SELECT 'Дочерних товаров (ДОЛЖНО БЫТЬ 0):', COUNT(*) FROM product WHERE parent_product_id IS NOT NULL AND poizon_id IS NOT NULL
UNION SELECT 'Размеров:', COUNT(*) FROM product_size WHERE product_id IN (SELECT id FROM product WHERE poizon_id IS NOT NULL)
UNION SELECT 'Характеристик:', COUNT(*) FROM product_characteristic WHERE product_id IN (SELECT id FROM product WHERE poizon_id IS NOT NULL)
UNION SELECT 'Изображений:', COUNT(*) FROM product_image WHERE product_id IN (SELECT id FROM product WHERE poizon_id IS NOT NULL);

-- Детально по товарам
SELECT 
    p.id, 
    p.name, 
    p.poizon_id,
    COUNT(DISTINCT ps.id) as sizes_count,
    COUNT(DISTINCT pi.id) as images_count,
    COUNT(DISTINCT pc.id) as chars_count
FROM product p
LEFT JOIN product_size ps ON p.id = ps.product_id
LEFT JOIN product_image pi ON p.id = pi.product_id
LEFT JOIN product_characteristic pc ON p.id = pc.product_id
WHERE p.poizon_id IS NOT NULL AND p.parent_product_id IS NULL
GROUP BY p.id
ORDER BY p.id;
EOF

echo ""
echo "✅ Готово!"
