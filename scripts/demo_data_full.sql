-- Полные демо-данные для проверки функционала
-- Дата: 30.03.2026

-- Очистка старых тестовых данных
DELETE FROM `order_item` WHERE order_id IN (SELECT id FROM `order` WHERE order_number LIKE 'TEST-%' OR order_number LIKE 'DEMO-%');
DELETE FROM `order_history` WHERE order_id IN (SELECT id FROM `order` WHERE order_number LIKE 'TEST-%' OR order_number LIKE 'DEMO-%');
DELETE FROM `order` WHERE order_number LIKE 'TEST-%' OR order_number LIKE 'DEMO-%';

-- Демо-клиенты (10 клиентов)
INSERT INTO `customer` (`email`, `phone`, `password_hash`, `first_name`, `last_name`, `default_city`, `default_address`, `orders_count`, `total_spent`, `status`, `created_at`, `updated_at`) VALUES
('ivanov@example.com', '+375291234567', '$2y$13$dummyhash1', 'Иван', 'Иванов', 'Минск', 'ул. Независимости 123, кв. 45', 5, 6250.00, 10, UNIX_TIMESTAMP()-2592000, UNIX_TIMESTAMP()),
('petrova@example.com', '+375337654321', '$2y$13$dummyhash2', 'Мария', 'Петрова', 'Гродно', 'ул. Советская 45, кв. 12', 3, 2670.00, 10, UNIX_TIMESTAMP()-1728000, UNIX_TIMESTAMP()),
('sidorov@example.com', '+375449876543', '$2y$13$dummyhash3', 'Алексей', 'Сидоров', 'Витебск', 'ул. Ленина 78, кв. 34', 7, 14700.00, 10, UNIX_TIMESTAMP()-3456000, UNIX_TIMESTAMP()),
('kozlova@example.com', '+375295432109', '$2y$13$dummyhash4', 'Елена', 'Козлова', 'Брест', 'пр. Мира 234, кв. 56', 2, 900.00, 10, UNIX_TIMESTAMP()-864000, UNIX_TIMESTAMP()),
('morozov@example.com', '+375331234567', '$2y$13$dummyhash5', 'Дмитрий', 'Морозов', 'Могилев', 'ул. Первомайская 67, кв. 89', 4, 7000.00, 10, UNIX_TIMESTAMP()-4320000, UNIX_TIMESTAMP()),
('volkova@example.com', '+375447778899', '$2y$13$dummyhash6', 'Анна', 'Волкова', 'Минск', 'пр. Победителей 15, кв. 101', 6, 9800.00, 10, UNIX_TIMESTAMP()-1296000, UNIX_TIMESTAMP()),
('sokolov@example.com', '+375293334455', '$2y$13$dummyhash7', 'Сергей', 'Соколов', 'Гомель', 'ул. Кирова 89, кв. 23', 1, 450.00, 10, UNIX_TIMESTAMP()-604800, UNIX_TIMESTAMP()),
('novikova@example.com', '+375336667788', '$2y$13$dummyhash8', 'Ольга', 'Новикова', 'Минск', 'ул. Богдановича 56, кв. 78', 8, 16500.00, 10, UNIX_TIMESTAMP()-5184000, UNIX_TIMESTAMP()),
('lebedev@example.com', '+375441112233', '$2y$13$dummyhash9', 'Андрей', 'Лебедев', 'Витебск', 'ул. Чкалова 34, кв. 12', 3, 4200.00, 10, UNIX_TIMESTAMP()-2160000, UNIX_TIMESTAMP()),
('smirnova@example.com', '+375295556677', '$2y$13$dummyhash10', 'Татьяна', 'Смирнова', 'Брест', 'ул. Московская 123, кв. 45', 5, 8750.00, 10, UNIX_TIMESTAMP()-3024000, UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updated_at` = UNIX_TIMESTAMP();

-- Демо-товары (15 товаров)
INSERT INTO `product` (`name`, `slug`, `sku`, `price`, `old_price`, `description`, `is_active`, `stock_status`, `created_at`, `updated_at`) VALUES
('Nike Air Max 90', 'nike-air-max-90', 'NIKE-AM90-001', 350.00, 420.00, 'Классические кроссовки Nike Air Max 90 с легендарной амортизацией Air. Верх из комбинации кожи и текстиля.', 1, 'in_stock', UNIX_TIMESTAMP()-5184000, UNIX_TIMESTAMP()),
('Adidas Ultraboost 22', 'adidas-ultraboost-22', 'ADIDAS-UB22-002', 280.00, 350.00, 'Беговые кроссовки с технологией Boost для максимального возврата энергии. Верх Primeknit+.', 1, 'in_stock', UNIX_TIMESTAMP()-4320000, UNIX_TIMESTAMP()),
('Jordan 1 Retro High', 'jordan-1-retro-high', 'JORDAN-1RH-003', 420.00, 500.00, 'Легендарные баскетбольные кроссовки Air Jordan 1 в высоком исполнении. Премиум кожа.', 1, 'in_stock', UNIX_TIMESTAMP()-3456000, UNIX_TIMESTAMP()),
('New Balance 550', 'new-balance-550', 'NB-550-004', 180.00, 220.00, 'Ретро-баскетбольные кроссовки New Balance 550 с классическим дизайном 80-х годов.', 1, 'in_stock', UNIX_TIMESTAMP()-2592000, UNIX_TIMESTAMP()),
('Yeezy Boost 350 V2', 'yeezy-boost-350-v2', 'YEEZY-350-005', 520.00, 650.00, 'Культовые кроссовки от Kanye West с технологией Boost и уникальным дизайном.', 1, 'low_stock', UNIX_TIMESTAMP()-1728000, UNIX_TIMESTAMP()),
('Nike Dunk Low', 'nike-dunk-low', 'NIKE-DL-006', 300.00, 380.00, 'Скейтбордические кроссовки Nike Dunk Low с классической цветовой схемой.', 1, 'in_stock', UNIX_TIMESTAMP()-864000, UNIX_TIMESTAMP()),
('Adidas Samba', 'adidas-samba', 'ADIDAS-SMB-007', 140.00, 180.00, 'Классические футбольные кроссовки Adidas Samba с замшевым верхом и резиновой подошвой.', 1, 'in_stock', UNIX_TIMESTAMP()-604800, UNIX_TIMESTAMP()),
('Converse Chuck 70', 'converse-chuck-70', 'CONVERSE-C70-008', 90.00, 120.00, 'Премиум версия легендарных кед Converse с улучшенной стелькой и усиленным носком.', 1, 'in_stock', UNIX_TIMESTAMP()-432000, UNIX_TIMESTAMP()),
('Puma Suede Classic', 'puma-suede-classic', 'PUMA-SC-009', 120.00, 150.00, 'Культовые кроссовки Puma Suede с замшевым верхом, популярные с 1968 года.', 1, 'in_stock', UNIX_TIMESTAMP()-259200, UNIX_TIMESTAMP()),
('Reebok Club C 85', 'reebok-club-c-85', 'REEBOK-CC85-010', 110.00, 140.00, 'Теннисные кроссовки Reebok Club C 85 в минималистичном дизайне.', 1, 'in_stock', UNIX_TIMESTAMP()-172800, UNIX_TIMESTAMP()),
('Vans Old Skool', 'vans-old-skool', 'VANS-OS-011', 85.00, 110.00, 'Классические скейтбордические кеды Vans Old Skool с узнаваемой боковой полосой.', 1, 'in_stock', UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP()),
('Asics Gel-Lyte III', 'asics-gel-lyte-3', 'ASICS-GL3-012', 160.00, 200.00, 'Беговые кроссовки Asics с технологией GEL и раздельным язычком.', 1, 'in_stock', UNIX_TIMESTAMP()-43200, UNIX_TIMESTAMP()),
('Salomon Speedcross 5', 'salomon-speedcross-5', 'SALOMON-SC5-013', 190.00, 240.00, 'Трейловые кроссовки Salomon с агрессивным протектором для бездорожья.', 1, 'low_stock', UNIX_TIMESTAMP()-21600, UNIX_TIMESTAMP()),
('Hoka One One Clifton 9', 'hoka-clifton-9', 'HOKA-C9-014', 210.00, 260.00, 'Максималистские беговые кроссовки Hoka с мягкой амортизацией.', 1, 'in_stock', UNIX_TIMESTAMP()-10800, UNIX_TIMESTAMP()),
('On Running Cloud 5', 'on-running-cloud-5', 'ON-C5-015', 200.00, 250.00, 'Швейцарские беговые кроссовки On с уникальной технологией CloudTec.', 1, 'in_stock', UNIX_TIMESTAMP()-3600, UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updated_at` = UNIX_TIMESTAMP();

-- Демо-заказы (12 заказов с разными статусами)
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
('DEMO-001', 'Иванов Иван Иванович', '+375291234567', 'ivanov@example.com', 'Dobropost', '2026-04-06', 'Беларусь', 'Минск', 'ул. Независимости 123, кв. 45', 'created', 1250.50, 1200, 35.50, 15, 0, MD5('DEMO001'), 1, UNIX_TIMESTAMP()-259200, UNIX_TIMESTAMP()-259200),
('DEMO-002', 'Петрова Мария Сергеевна', '+375337654321', 'petrova@example.com', 'EMS', '2026-04-09', 'Беларусь', 'Гродно', 'ул. Советская 45, кв. 12', 'confirmed', 890.00, 850, 25, 15, 0, MD5('DEMO002'), 1, UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP()-86400),
('DEMO-003', 'Сидоров Алексей Петрович', '+375449876543', 'sidorov@example.com', 'Самовывоз', '2026-04-02', 'Беларусь', 'Витебск', 'ул. Ленина 78, кв. 34', 'paid', 2100.75, 2000, 50.75, 50, 0, MD5('DEMO003'), 1, UNIX_TIMESTAMP()-432000, UNIX_TIMESTAMP()),
('DEMO-004', 'Козлова Елена Дмитриевна', '+375295432109', 'kozlova@example.com', 'Белпочта', '2026-04-13', 'Беларусь', 'Брест', 'пр. Мира 234, кв. 56', 'ordered', 450.25, 400, 30.25, 20, 0, MD5('DEMO004'), 1, UNIX_TIMESTAMP()-691200, UNIX_TIMESTAMP()-172800),
('DEMO-005', 'Морозов Дмитрий Александрович', '+375331234567', 'morozov@example.com', 'СДЭК', '2026-04-04', 'Беларусь', 'Могилев', 'ул. Первомайская 67, кв. 89', 'shipped', 1750.00, 1650, 45, 55, 0, MD5('DEMO005'), 1, UNIX_TIMESTAMP()-864000, UNIX_TIMESTAMP()-345600),
('DEMO-006', 'Волкова Анна Игоревна', '+375447778899', 'volkova@example.com', 'Dobropost', '2026-04-15', 'Беларусь', 'Минск', 'пр. Победителей 15, кв. 101', 'delivered', 3200.00, 3000, 100, 100, 0, MD5('DEMO006'), 1, UNIX_TIMESTAMP()-1036800, UNIX_TIMESTAMP()-518400),
('DEMO-007', 'Соколов Сергей Викторович', '+375293334455', 'sokolov@example.com', 'EMS', '2026-04-20', 'Беларусь', 'Гомель', 'ул. Кирова 89, кв. 23', 'created', 750.00, 700, 30, 20, 0, MD5('DEMO007'), 1, UNIX_TIMESTAMP()-3600, UNIX_TIMESTAMP()-3600),
('DEMO-008', 'Новикова Ольга Павловна', '+375336667788', 'novikova@example.com', 'СДЭК', '2026-04-18', 'Беларусь', 'Минск', 'ул. Богдановича 56, кв. 78', 'confirmed', 1890.50, 1800, 55.50, 35, 0, MD5('DEMO008'), 1, UNIX_TIMESTAMP()-7200, UNIX_TIMESTAMP()-3600),
('DEMO-009', 'Лебедев Андрей Николаевич', '+375441112233', 'lebedev@example.com', 'Белпочта', '2026-04-25', 'Беларусь', 'Витебск', 'ул. Чкалова 34, кв. 12', 'paid', 1400.00, 1320, 45, 35, 0, MD5('DEMO009'), 1, UNIX_TIMESTAMP()-172800, UNIX_TIMESTAMP()-86400),
('DEMO-010', 'Смирнова Татьяна Владимировна', '+375295556677', 'smirnova@example.com', 'Dobropost', '2026-04-22', 'Беларусь', 'Брест', 'ул. Московская 123, кв. 45', 'ordered', 2250.00, 2100, 75, 75, 0, MD5('DEMO010'), 1, UNIX_TIMESTAMP()-345600, UNIX_TIMESTAMP()-259200),
('DEMO-011', 'Иванов Иван Иванович', '+375291234567', 'ivanov@example.com', 'EMS', '2026-04-28', 'Беларусь', 'Минск', 'ул. Независимости 123, кв. 45', 'created', 950.00, 900, 30, 20, 0, MD5('DEMO011'), 1, UNIX_TIMESTAMP()-1800, UNIX_TIMESTAMP()-1800),
('DEMO-012', 'Петрова Мария Сергеевна', '+375337654321', 'petrova@example.com', 'Самовывоз', '2026-04-30', 'Беларусь', 'Гродно', 'ул. Советская 45, кв. 12', 'created', 680.00, 650, 20, 10, 0, MD5('DEMO012'), 1, UNIX_TIMESTAMP()-900, UNIX_TIMESTAMP()-900);

-- Получаем ID заказов
SET @demo1 = (SELECT id FROM `order` WHERE order_number = 'DEMO-001' LIMIT 1);
SET @demo2 = (SELECT id FROM `order` WHERE order_number = 'DEMO-002' LIMIT 1);
SET @demo3 = (SELECT id FROM `order` WHERE order_number = 'DEMO-003' LIMIT 1);
SET @demo4 = (SELECT id FROM `order` WHERE order_number = 'DEMO-004' LIMIT 1);
SET @demo5 = (SELECT id FROM `order` WHERE order_number = 'DEMO-005' LIMIT 1);
SET @demo6 = (SELECT id FROM `order` WHERE order_number = 'DEMO-006' LIMIT 1);
SET @demo7 = (SELECT id FROM `order` WHERE order_number = 'DEMO-007' LIMIT 1);
SET @demo8 = (SELECT id FROM `order` WHERE order_number = 'DEMO-008' LIMIT 1);
SET @demo9 = (SELECT id FROM `order` WHERE order_number = 'DEMO-009' LIMIT 1);
SET @demo10 = (SELECT id FROM `order` WHERE order_number = 'DEMO-010' LIMIT 1);
SET @demo11 = (SELECT id FROM `order` WHERE order_number = 'DEMO-011' LIMIT 1);
SET @demo12 = (SELECT id FROM `order` WHERE order_number = 'DEMO-012' LIMIT 1);

-- Товары для заказов
INSERT INTO `order_item` (`order_id`, `product_name`, `product_sku`, `quantity`, `price`, `size`, `color`) VALUES
(@demo1, 'Nike Air Max 90', 'NIKE-AM90-001', 1, 350.00, '42', 'Белый/Черный'),
(@demo2, 'Adidas Ultraboost 22', 'ADIDAS-UB22-002', 1, 280.00, '43', 'Синий'),
(@demo3, 'Jordan 1 Retro High', 'JORDAN-1RH-003', 1, 420.00, '44', 'Красный/Белый'),
(@demo4, 'New Balance 550', 'NB-550-004', 1, 180.00, '41', 'Белый/Зеленый'),
(@demo5, 'Yeezy Boost 350 V2', 'YEEZY-350-005', 1, 520.00, '42.5', 'Серый'),
(@demo6, 'Nike Dunk Low', 'NIKE-DL-006', 2, 300.00, '43', 'Черный/Белый'),
(@demo7, 'Adidas Samba', 'ADIDAS-SMB-007', 1, 140.00, '42', 'Черный'),
(@demo8, 'Converse Chuck 70', 'CONVERSE-C70-008', 2, 90.00, '41', 'Белый'),
(@demo9, 'Puma Suede Classic', 'PUMA-SC-009', 2, 120.00, '43', 'Синий'),
(@demo10, 'Reebok Club C 85', 'REEBOK-CC85-010', 2, 110.00, '42', 'Белый'),
(@demo11, 'Vans Old Skool', 'VANS-OS-011', 2, 85.00, '44', 'Черный'),
(@demo12, 'Asics Gel-Lyte III', 'ASICS-GL3-012', 1, 160.00, '42', 'Серый/Красный');

-- История изменений заказов
INSERT INTO `order_history` (`order_id`, `status`, `comment`, `created_by`, `created_at`) VALUES
(@demo1, 'created', 'Заказ создан клиентом через сайт', 1, UNIX_TIMESTAMP()-259200),
(@demo2, 'created', 'Заказ создан клиентом', 1, UNIX_TIMESTAMP()-86400),
(@demo2, 'confirmed', 'Заказ подтвержден менеджером Иваном', 1, UNIX_TIMESTAMP()-86400+3600),
(@demo3, 'created', 'Заказ создан клиентом', 1, UNIX_TIMESTAMP()-432000),
(@demo3, 'confirmed', 'Заказ подтвержден менеджером', 1, UNIX_TIMESTAMP()-432000+3600),
(@demo3, 'paid', 'Оплата получена через ЕРИП', 1, UNIX_TIMESTAMP()-345600),
(@demo4, 'created', 'Заказ создан клиентом', 1, UNIX_TIMESTAMP()-691200),
(@demo4, 'confirmed', 'Заказ подтвержден менеджером', 1, UNIX_TIMESTAMP()-691200+3600),
(@demo4, 'paid', 'Оплата получена', 1, UNIX_TIMESTAMP()-604800),
(@demo4, 'ordered', 'Товар заказан у поставщика', 1, UNIX_TIMESTAMP()-172800),
(@demo5, 'created', 'Заказ создан клиентом', 1, UNIX_TIMESTAMP()-864000),
(@demo5, 'confirmed', 'Заказ подтвержден', 1, UNIX_TIMESTAMP()-864000+3600),
(@demo5, 'paid', 'Оплата получена', 1, UNIX_TIMESTAMP()-777600),
(@demo5, 'ordered', 'Товар заказан', 1, UNIX_TIMESTAMP()-691200),
(@demo5, 'shipped', 'Заказ отправлен клиенту', 1, UNIX_TIMESTAMP()-345600),
(@demo6, 'created', 'Заказ создан', 1, UNIX_TIMESTAMP()-1036800),
(@demo6, 'confirmed', 'Подтвержден', 1, UNIX_TIMESTAMP()-1036800+3600),
(@demo6, 'paid', 'Оплачен', 1, UNIX_TIMESTAMP()-950400),
(@demo6, 'ordered', 'Заказан', 1, UNIX_TIMESTAMP()-864000),
(@demo6, 'shipped', 'Отправлен', 1, UNIX_TIMESTAMP()-691200),
(@demo6, 'delivered', 'Доставлен клиенту', 1, UNIX_TIMESTAMP()-518400);

-- Успешное завершение
SELECT 'Демо-данные успешно созданы!' as message;
SELECT COUNT(*) as total_customers FROM `customer`;
SELECT COUNT(*) as total_products FROM `product`;
SELECT COUNT(*) as total_orders FROM `order` WHERE order_number LIKE 'DEMO-%';
