-- Создание тестовых заказов для проверки функционала
-- Дата: 30.03.2026

-- Тестовые клиенты
INSERT INTO `customer` (`first_name`, `last_name`, `email`, `phone`, `address`, `city`, `status`, `loyalty_points`, `created_at`, `updated_at`) VALUES
('Иван', 'Иванов', 'ivanov.test@example.com', '+375291234567', 'ул. Независимости 123, кв. 45', 'Минск', 'active', 150, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Петр', 'Петров', 'petrov.test@example.com', '+375337654321', 'ул. Советская 45, кв. 12', 'Гродно', 'active', 85, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Анна', 'Сидорова', 'sidorova.test@example.com', '+375449876543', 'ул. Ленина 78, кв. 34', 'Витебск', 'active', 220, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Дмитрий', 'Козлов', 'kozlov.test@example.com', '+375295432109', 'пр. Мира 234, кв. 56', 'Брест', 'active', 45, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('Елена', 'Морозова', 'morozova.test@example.com', '+375331234567', 'ул. Первомайская 67, кв. 89', 'Могилев', 'active', 310, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updated_at` = UNIX_TIMESTAMP();

-- Тестовые заказы с токенами
INSERT INTO `order` (
    `order_number`, 
    `client_name`, 
    `client_phone`, 
    `client_email`, 
    `delivery_method`, 
    `delivery_date`, 
    `delivery_country`, 
    `city`, 
    `full_address`, 
    `status`, 
    `total_amount`, 
    `product_price`, 
    `logistics_price`, 
    `commission_price`, 
    `delivery_cost`,
    `token`,
    `created_by`, 
    `created_at`, 
    `updated_at`
) VALUES
('TEST-001', 'Иванов Иван Иванович', '+375291234567', 'ivanov.test@example.com', 'Dobropost', '2026-04-06', 'Беларусь', 'Минск', 'ул. Независимости 123, кв. 45', 'created', 1250.50, 1200, 35.50, 15, 0, MD5(CONCAT('TEST-001', UNIX_TIMESTAMP())), 1, UNIX_TIMESTAMP() - 86400*3, UNIX_TIMESTAMP() - 86400*3),
('TEST-002', 'Петров Петр Петрович', '+375337654321', 'petrov.test@example.com', 'EMS', '2026-04-09', 'Беларусь', 'Гродно', 'ул. Советская 45, кв. 12', 'confirmed', 890.00, 850, 25, 15, 0, MD5(CONCAT('TEST-002', UNIX_TIMESTAMP())), 1, UNIX_TIMESTAMP() - 86400*1, UNIX_TIMESTAMP() - 86400*1),
('TEST-003', 'Сидорова Анна Михайловна', '+375449876543', 'sidorova.test@example.com', 'Самовывоз', '2026-04-02', 'Беларусь', 'Витебск', 'ул. Ленина 78, кв. 34', 'paid', 2100.75, 2000, 50.75, 50, 0, MD5(CONCAT('TEST-003', UNIX_TIMESTAMP())), 1, UNIX_TIMESTAMP() - 86400*5, UNIX_TIMESTAMP()),
('TEST-004', 'Козлов Дмитрий Сергеевич', '+375295432109', 'kozlov.test@example.com', 'Белпочта', '2026-04-13', 'Беларусь', 'Брест', 'пр. Мира 234, кв. 56', 'shipped', 450.25, 400, 30.25, 20, 0, MD5(CONCAT('TEST-004', UNIX_TIMESTAMP())), 1, UNIX_TIMESTAMP() - 86400*8, UNIX_TIMESTAMP() - 86400*2),
('TEST-005', 'Морозова Елена Александровна', '+375331234567', 'morozova.test@example.com', 'СДЭК', '2026-04-04', 'Беларусь', 'Могилев', 'ул. Первомайская 67, кв. 89', 'delivered', 1750.00, 1650, 45, 55, 0, MD5(CONCAT('TEST-005', UNIX_TIMESTAMP())), 1, UNIX_TIMESTAMP() - 86400*10, UNIX_TIMESTAMP() - 86400*4),
('TEST-006', 'Иванов Иван Иванович', '+375291234567', 'ivanov.test@example.com', 'Dobropost', '2026-04-15', 'Беларусь', 'Минск', 'ул. Независимости 123, кв. 45', 'ordered', 3200.00, 3000, 100, 100, 0, MD5(CONCAT('TEST-006', UNIX_TIMESTAMP())), 1, UNIX_TIMESTAMP() - 86400*2, UNIX_TIMESTAMP() - 86400*1),
('TEST-007', 'Петров Петр Петрович', '+375337654321', 'petrov.test@example.com', 'EMS', '2026-04-20', 'Беларусь', 'Гродно', 'ул. Советская 45, кв. 12', 'created', 750.00, 700, 30, 20, 0, MD5(CONCAT('TEST-007', UNIX_TIMESTAMP())), 1, UNIX_TIMESTAMP() - 3600, UNIX_TIMESTAMP() - 3600),
('TEST-008', 'Сидорова Анна Михайловна', '+375449876543', 'sidorova.test@example.com', 'СДЭК', '2026-04-18', 'Беларусь', 'Витебск', 'ул. Ленина 78, кв. 34', 'confirmed', 1890.50, 1800, 55.50, 35, 0, MD5(CONCAT('TEST-008', UNIX_TIMESTAMP())), 1, UNIX_TIMESTAMP() - 7200, UNIX_TIMESTAMP() - 3600);

-- Получаем ID последних вставленных заказов
SET @order1 = (SELECT id FROM `order` WHERE order_number = 'TEST-001' LIMIT 1);
SET @order2 = (SELECT id FROM `order` WHERE order_number = 'TEST-002' LIMIT 1);
SET @order3 = (SELECT id FROM `order` WHERE order_number = 'TEST-003' LIMIT 1);
SET @order4 = (SELECT id FROM `order` WHERE order_number = 'TEST-004' LIMIT 1);
SET @order5 = (SELECT id FROM `order` WHERE order_number = 'TEST-005' LIMIT 1);
SET @order6 = (SELECT id FROM `order` WHERE order_number = 'TEST-006' LIMIT 1);
SET @order7 = (SELECT id FROM `order` WHERE order_number = 'TEST-007' LIMIT 1);
SET @order8 = (SELECT id FROM `order` WHERE order_number = 'TEST-008' LIMIT 1);

-- Товары для заказов
INSERT INTO `order_item` (`order_id`, `product_name`, `product_sku`, `quantity`, `price`, `size`, `color`) VALUES
(@order1, 'Nike Air Max 90', 'NIKE-AM90-001', 1, 1200.00, '42', 'Белый/Черный'),
(@order2, 'Adidas Ultraboost 22', 'ADIDAS-UB22-002', 1, 850.00, '43', 'Синий'),
(@order3, 'Jordan 1 Retro High', 'JORDAN-1RH-003', 1, 2000.00, '44', 'Красный/Белый'),
(@order4, 'New Balance 550', 'NB-550-004', 1, 400.00, '41', 'Белый/Зеленый'),
(@order5, 'Yeezy Boost 350', 'YEEZY-350-005', 1, 1650.00, '42.5', 'Серый'),
(@order6, 'Nike Dunk Low', 'NIKE-DL-006', 2, 1500.00, '43', 'Черный/Белый'),
(@order7, 'Adidas Samba', 'ADIDAS-SMB-007', 1, 700.00, '42', 'Черный'),
(@order8, 'Converse Chuck 70', 'CONVERSE-C70-008', 2, 900.00, '41', 'Белый');

-- История изменений заказов
INSERT INTO `order_history` (`order_id`, `status`, `comment`, `created_by`, `created_at`) VALUES
(@order1, 'created', 'Заказ создан клиентом', 1, UNIX_TIMESTAMP() - 86400*3),
(@order2, 'created', 'Заказ создан клиентом', 1, UNIX_TIMESTAMP() - 86400*1),
(@order2, 'confirmed', 'Заказ подтвержден менеджером', 1, UNIX_TIMESTAMP() - 86400*1 + 3600),
(@order3, 'created', 'Заказ создан клиентом', 1, UNIX_TIMESTAMP() - 86400*5),
(@order3, 'confirmed', 'Заказ подтвержден менеджером', 1, UNIX_TIMESTAMP() - 86400*5 + 3600),
(@order3, 'paid', 'Оплата получена', 1, UNIX_TIMESTAMP() - 86400*4),
(@order4, 'created', 'Заказ создан клиентом', 1, UNIX_TIMESTAMP() - 86400*8),
(@order4, 'confirmed', 'Заказ подтвержден менеджером', 1, UNIX_TIMESTAMP() - 86400*8 + 3600),
(@order4, 'paid', 'Оплата получена', 1, UNIX_TIMESTAMP() - 86400*7),
(@order4, 'shipped', 'Заказ отправлен', 1, UNIX_TIMESTAMP() - 86400*2),
(@order5, 'created', 'Заказ создан клиентом', 1, UNIX_TIMESTAMP() - 86400*10),
(@order5, 'confirmed', 'Заказ подтвержден менеджером', 1, UNIX_TIMESTAMP() - 86400*10 + 3600),
(@order5, 'paid', 'Оплата получена', 1, UNIX_TIMESTAMP() - 86400*9),
(@order5, 'shipped', 'Заказ отправлен', 1, UNIX_TIMESTAMP() - 86400*6),
(@order5, 'delivered', 'Заказ доставлен', 1, UNIX_TIMESTAMP() - 86400*4);

-- Успешное завершение
SELECT 'Тестовые заказы успешно созданы!' as message;
SELECT COUNT(*) as total_orders FROM `order` WHERE order_number LIKE 'TEST-%';
