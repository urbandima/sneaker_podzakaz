-- Создание демо-пользователей для тестирования

-- Обычный пользователь
INSERT INTO customer (email, phone, password_hash, auth_key, first_name, last_name, status, created_at, updated_at) 
VALUES (
    'demo@sneakerhead.by',
    '+375291234567',
    '$2y$13$9VQ8YjQjQjQjQjQjQjQjQu', -- demo123
    'demo_auth_key_12345',
    'Демо',
    'Пользователь',
    10,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    auth_key = VALUES(auth_key),
    updated_at = UNIX_TIMESTAMP();

-- VIP пользователь
INSERT INTO customer (email, phone, password_hash, auth_key, first_name, last_name, status, created_at, updated_at) 
VALUES (
    'vip@sneakerhead.by',
    '+375337654321',
    '$2y$13$9VQ8YjQjQjQjQjQjQjQjQu', -- vip123
    'vip_auth_key_67890',
    'VIP',
    'Клиент',
    10,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    auth_key = VALUES(auth_key),
    updated_at = UNIX_TIMESTAMP();

-- Администратор (для тестирования)
INSERT INTO customer (email, phone, password_hash, auth_key, first_name, last_name, status, created_at, updated_at) 
VALUES (
    'admin@sneakerhead.by',
    '+375449876543',
    '$2y$13$9VQ8YjQjQjQjQjQjQjQjQu', -- admin123
    'admin_auth_key_11111',
    'Админ',
    'Системы',
    10,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    auth_key = VALUES(auth_key),
    updated_at = UNIX_TIMESTAMP();
