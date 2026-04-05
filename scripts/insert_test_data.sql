-- Тестовые данные для проверки функционала

-- Очистка старых тестовых данных
DELETE FROM order_history WHERE order_id IN (SELECT id FROM `order` WHERE client_email LIKE '%@example.com');
DELETE FROM order_item WHERE order_id IN (SELECT id FROM `order` WHERE client_email LIKE '%@example.com');
DELETE FROM `order` WHERE client_email LIKE '%@example.com';
DELETE FROM customer WHERE email LIKE '%@example.com';
DELETE FROM product WHERE slug IN ('nike-air-max-90', 'adidas-yeezy-boost-350', 'jordan-1-retro-high', 'new-balance-574', 'puma-suede-classic');

-- Тестовые клиенты
INSERT INTO customer (first_name, last_name, email, phone, status, loyalty_points, created_at, updated_at) VALUES
('Иван', 'Петров', 'ivan.petrov@example.com', '+375291234567', 'active', 150, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Мария', 'Сидорова', 'maria.sidorova@example.com', '+375297654321', 'active', 320, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Алексей', 'Козлов', 'alex.kozlov@example.com', '+375293456789', 'active', 80, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Ольга', 'Новикова', 'olga.novikova@example.com', '+375295678901', 'active', 500, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Дмитрий', 'Волков', 'dmitry.volkov@example.com', '+375298765432', 'active', 45, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Тестовые товары
INSERT INTO product (name, slug, description, price, price_cny, category_id, brand, is_active, stock_quantity, created_at, updated_at) VALUES
('Nike Air Max 90', 'nike-air-max-90', 'Классические кроссовки Nike Air Max 90 с видимой амортизацией Air', 450.00, 1200.00, 1, 'Nike', 1, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Adidas Yeezy Boost 350', 'adidas-yeezy-boost-350', 'Культовые кроссовки от Kanye West с технологией Boost', 890.00, 2400.00, 1, 'Adidas', 1, 8, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Jordan 1 Retro High', 'jordan-1-retro-high', 'Легендарные баскетбольные кроссовки Air Jordan 1', 650.00, 1800.00, 1, 'Jordan', 1, 12, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('New Balance 574', 'new-balance-574', 'Классические кроссовки New Balance 574 в ретро стиле', 320.00, 900.00, 1, 'New Balance', 1, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Puma Suede Classic', 'puma-suede-classic', 'Культовые замшевые кроссовки Puma Suede', 280.00, 750.00, 1, 'Puma', 1, 18, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Тестовые заказы
SET @customer1 = (SELECT id FROM customer WHERE email = 'ivan.petrov@example.com');
SET @customer2 = (SELECT id FROM customer WHERE email = 'maria.sidorova@example.com');
SET @customer3 = (SELECT id FROM customer WHERE email = 'alex.kozlov@example.com');
SET @customer4 = (SELECT id FROM customer WHERE email = 'olga.novikova@example.com');
SET @customer5 = (SELECT id FROM customer WHERE email = 'dmitry.volkov@example.com');

SET @product1 = (SELECT id FROM product WHERE slug = 'nike-air-max-90');
SET @product2 = (SELECT id FROM product WHERE slug = 'adidas-yeezy-boost-350');
SET @product3 = (SELECT id FROM product WHERE slug = 'jordan-1-retro-high');
SET @product4 = (SELECT id FROM product WHERE slug = 'new-balance-574');
SET @product5 = (SELECT id FROM product WHERE slug = 'puma-suede-classic');

INSERT INTO `order` (customer_id, client_name, client_phone, client_email, status, total_amount, address, delivery_method, payment_method, payment_status, token, created_at, updated_at) VALUES
(@customer1, 'Иван Петров', '+375291234567', 'ivan.petrov@example.com', 'new', 450.00, 'г. Минск, ул. Ленина, д. 10, кв. 5', 'Европочта', 'Наличные', 0, MD5(RAND()), UNIX_TIMESTAMP() - 3600, UNIX_TIMESTAMP() - 3600),
(@customer2, 'Мария Сидорова', '+375297654321', 'maria.sidorova@example.com', 'paid', 890.00, 'г. Минск, пр. Независимости, д. 25, кв. 12', 'СДЭК', 'Онлайн', 1, MD5(RAND()), UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP() - 7200),
(@customer3, 'Алексей Козлов', '+375293456789', 'alex.kozlov@example.com', 'ordered', 650.00, 'г. Гомель, ул. Советская, д. 45', 'Белпочта', 'Онлайн', 1, MD5(RAND()), UNIX_TIMESTAMP() - 172800, UNIX_TIMESTAMP() - 86400),
(@customer4, 'Ольга Новикова', '+375295678901', 'olga.novikova@example.com', 'warehouse', 320.00, 'г. Брест, ул. Московская, д. 78, кв. 3', 'Европочта', 'Онлайн', 1, MD5(RAND()), UNIX_TIMESTAMP() - 259200, UNIX_TIMESTAMP() - 3600),
(@customer5, 'Дмитрий Волков', '+375298765432', 'dmitry.volkov@example.com', 'completed', 280.00, 'г. Витебск, ул. Ленина, д. 12', 'Самовывоз', 'Наличные', 1, MD5(RAND()), UNIX_TIMESTAMP() - 604800, UNIX_TIMESTAMP() - 86400);

-- Товары в заказах
SET @order1 = (SELECT id FROM `order` WHERE client_email = 'ivan.petrov@example.com' LIMIT 1);
SET @order2 = (SELECT id FROM `order` WHERE client_email = 'maria.sidorova@example.com' LIMIT 1);
SET @order3 = (SELECT id FROM `order` WHERE client_email = 'alex.kozlov@example.com' LIMIT 1);
SET @order4 = (SELECT id FROM `order` WHERE client_email = 'olga.novikova@example.com' LIMIT 1);
SET @order5 = (SELECT id FROM `order` WHERE client_email = 'dmitry.volkov@example.com' LIMIT 1);

INSERT INTO order_item (order_id, product_id, product_name, size, quantity, price) VALUES
(@order1, @product1, 'Nike Air Max 90', '42', 1, 450.00),
(@order2, @product2, 'Adidas Yeezy Boost 350', '43', 1, 890.00),
(@order3, @product3, 'Jordan 1 Retro High', '44', 1, 650.00),
(@order4, @product4, 'New Balance 574', '41', 1, 320.00),
(@order5, @product5, 'Puma Suede Classic', '42', 1, 280.00);

-- Настройки компании (если не существуют)
INSERT IGNORE INTO company_settings (id, name, email, phone, address, unp, bank_details, created_at, updated_at) VALUES
(1, 'СНИКЕРХЭД', 'info@sneakerhead.by', '+375291234567', 'г. Минск, ул. Ленина, д. 1', '123456789', 'BY12 ALFA 1234 5678 9012 3456 7890', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Курс валют (если не существует)
INSERT IGNORE INTO exchange_rate (id, currency, rate, source, updated_at) VALUES
(1, 'CNY', 0.28, 'NBRB', UNIX_TIMESTAMP());

SELECT 'Тестовые данные успешно добавлены!' as result;
