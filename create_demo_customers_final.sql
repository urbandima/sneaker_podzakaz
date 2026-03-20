-- Создание демо-пользователей для тестирования

-- Обычный пользователь
INSERT INTO customer (email, phone, password_hash, auth_key, first_name, last_name, status, created_at, updated_at) 
VALUES (
    'demo@sneakerhead.by',
    '+375291234567',
    '$2y$12$zQo1DZMj3eLLVtpT4KZVz.k3gr.9AQK8R/EbQc7U128M83p4a1RJS',
    'demo_auth_key_1773816890',
    'Демо',
    'Пользователь',
    10,
    1773816890,
    1773816890
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    auth_key = VALUES(auth_key),
    updated_at = 1773816890;

-- VIP пользователь
INSERT INTO customer (email, phone, password_hash, auth_key, first_name, last_name, status, created_at, updated_at) 
VALUES (
    'vip@sneakerhead.by',
    '+375337654321',
    '$2y$12$nmHVEfbybt2iJxwIjT0AwOFNBMlxx7UERokWfd0xwI0J2GUiq2eYK',
    'vip_auth_key_1773816891',
    'VIP',
    'Клиент',
    10,
    1773816890,
    1773816890
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    auth_key = VALUES(auth_key),
    updated_at = 1773816890;

-- Администратор
INSERT INTO customer (email, phone, password_hash, auth_key, first_name, last_name, status, created_at, updated_at) 
VALUES (
    'admin@sneakerhead.by',
    '+375449876543',
    '$2y$12$CRPqAtwnKRLuoOxURR2iF.X4/IQJj9WaVXmoBAjyKx8q8L8JRxzvy',
    'admin_auth_key_1773816892',
    'Админ',
    'Системы',
    10,
    1773816890,
    1773816890
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    auth_key = VALUES(auth_key),
    updated_at = 1773816890;