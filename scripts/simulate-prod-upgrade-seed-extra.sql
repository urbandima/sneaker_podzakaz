-- CMP-412: дополнительные данные для симуляции апгрейда прод-базы.
-- Использует только колонки, существующие с самых ранних миграций (customer/
-- order/order_item/coupon/category появляются в первой волне схемы), поэтому
-- безопасно применять поверх любой базовой точки, которую пробует
-- scripts/simulate-prod-upgrade.sh.
SET NAMES utf8mb4;
SET SESSION sql_mode = '';

INSERT INTO customer (email, phone, first_name, last_name, password_hash, status, created_at, updated_at) VALUES
('sim.ivan@example.test',   '+375291110001', 'Иван',   'Симуляторов', '$2y$10$00000000000000000000000000000000000000000000000000', 10, UNIX_TIMESTAMP() - 86400*120, UNIX_TIMESTAMP() - 86400*5),
('sim.maria@example.test',  '+375291110002', 'Мария',  'Тестова',     '$2y$10$00000000000000000000000000000000000000000000000000', 10, UNIX_TIMESTAMP() - 86400*100, UNIX_TIMESTAMP() - 86400*4),
('sim.oleg@example.test',   '+375291110003', 'Олег',   'Прогонов',    '$2y$10$00000000000000000000000000000000000000000000000000', 10, UNIX_TIMESTAMP() - 86400*80,  UNIX_TIMESTAMP() - 86400*3);

INSERT INTO coupon (code, name, description, type, value, min_order_amount, is_active, is_first_order, current_uses, created_at, updated_at) VALUES
('SIM-WELCOME10', 'Симуляция: приветственная скидка 10%', 'CMP-412', 'percentage', 10.00, 100.00, 1, 1, 5, NOW() - INTERVAL 90 DAY, NOW() - INTERVAL 5 DAY),
('SIM-SALE20',    'Симуляция: скидка 20 руб',             'CMP-412', 'fixed',      20.00, 150.00, 1, 0, 12, NOW() - INTERVAL 60 DAY, NOW() - INTERVAL 2 DAY);

-- order/order_item: только гарантированно-древние колонки. token/client_name/
-- created_by нужны почти на всех точках истории — если какой-то колонки нет
-- (совсем ранняя база), эта секция упадёт и её нужно расширить/урезать вручную
-- под конкретный кандидат (см. docs/deploy/cmp405-main-to-prod-runbook.md).
INSERT INTO `order` (customer_id, order_number, token, client_name, client_email, full_address, total_amount, status, source, created_by, created_at, updated_at)
SELECT c.id, CONCAT('ORD-SIM-', c.id), MD5(CONCAT('cmp412-sim-', c.id)), c.first_name, c.email, 'г. Минск, симуляция CMP-412', 199.00 + c.id, 'delivered', 'website', c.id, c.created_at, c.updated_at
FROM customer c WHERE c.email LIKE 'sim.%@example.test';

INSERT INTO order_item (order_id, product_id, product_name, quantity, price, total, created_at)
SELECT o.id, p.id, p.name, 1, p.price, p.price, o.created_at
FROM `order` o
JOIN customer c ON c.id = o.customer_id AND c.email LIKE 'sim.%@example.test'
JOIN product p ON p.id = ((o.customer_id % 5) + 1);
