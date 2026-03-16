-- Performance Optimization: Database Indexes for Catalog
-- Дата: 08.11.2025
-- Цель: Ускорение запросов getAvailableSizes() в каталоге

-- Индексы для размеров товаров (критично для производительности фильтров)
CREATE INDEX IF NOT EXISTS idx_product_size_eu ON product_size(eu_size, is_available);
CREATE INDEX IF NOT EXISTS idx_product_size_us ON product_size(us_size, is_available);
CREATE INDEX IF NOT EXISTS idx_product_size_uk ON product_size(uk_size, is_available);
CREATE INDEX IF NOT EXISTS idx_product_size_cm ON product_size(cm_size, is_available);

-- Композитный индекс для товаров (ускоряет фильтрацию активных товаров)
CREATE INDEX IF NOT EXISTS idx_product_active ON product(is_active, stock_status);

-- Дополнительные индексы для JOIN-ов
CREATE INDEX IF NOT EXISTS idx_product_size_product_id ON product_size(product_id, is_available);
CREATE INDEX IF NOT EXISTS idx_product_brand ON product(brand_id, is_active);
CREATE INDEX IF NOT EXISTS idx_product_category ON product(category_id, is_active);

-- Индекс для сортировки по цене
CREATE INDEX IF NOT EXISTS idx_product_price ON product(price, is_active);

-- Проверка созданных индексов
SHOW INDEX FROM product;
SHOW INDEX FROM product_size;

-- Ожидаемый эффект:
-- - getAvailableSizes(): 4 запроса ~200ms → ~15ms (в 13 раз быстрее)
-- - Общее время загрузки каталога: ~2s → ~300ms (в 6.5 раз быстрее)
