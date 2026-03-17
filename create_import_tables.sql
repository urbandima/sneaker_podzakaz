-- Создание таблиц для системы импорта

-- Таблица источников импорта
CREATE TABLE IF NOT EXISTS `import_source` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT 'Название источника',
    `code` varchar(50) NOT NULL COMMENT 'Код источника (lamoda, dewu, zalando, stockx)',
    `base_url` varchar(255) NOT NULL COMMENT 'Базовый URL',
    `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Активен ли источник',
    `parse_delay_min` int(11) NOT NULL DEFAULT '2' COMMENT 'Минимальная задержка между запросами (сек)',
    `parse_delay_max` int(11) NOT NULL DEFAULT '5' COMMENT 'Максимальная задержка между запросами (сек)',
    `proxy_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Использовать прокси',
    `proxy_list` text COMMENT 'JSON список прокси',
    `proxy_rotation` varchar(20) NOT NULL DEFAULT 'random' COMMENT 'Тип ротации прокси',
    `captcha_service` varchar(50) NOT NULL DEFAULT '2captcha' COMMENT 'Сервис CAPTCHA',
    `captcha_api_key` varchar(255) DEFAULT NULL COMMENT 'API ключ CAPTCHA',
    `captcha_fallback_service` varchar(50) DEFAULT NULL COMMENT 'Резервный сервис CAPTCHA',
    `captcha_fallback_api_key` varchar(255) DEFAULT NULL COMMENT 'API ключ резервного сервиса',
    `currency_code` varchar(3) NOT NULL COMMENT 'Код валюты источника',
    `settings` text COMMENT 'JSON с настройками',
    `last_run_at` datetime DEFAULT NULL COMMENT 'Последний запуск',
    `total_products_parsed` int(11) NOT NULL DEFAULT '0' COMMENT 'Всего товаров спарсено',
    `successful_runs` int(11) NOT NULL DEFAULT '0' COMMENT 'Успешных запусков',
    `failed_runs` int(11) NOT NULL DEFAULT '0' COMMENT 'Неудачных запусков',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_import_source_code` (`code`),
    KEY `idx_import_source_is_active` (`is_active`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Таблица задач импорта
CREATE TABLE IF NOT EXISTS `import_task` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `source_id` int(11) NOT NULL COMMENT 'ID источника',
    `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'Статус (pending, running, completed, failed, cancelled)',
    `total_products` int(11) NOT NULL DEFAULT '0' COMMENT 'Всего товаров в задаче',
    `processed_products` int(11) NOT NULL DEFAULT '0' COMMENT 'Обработано товаров',
    `imported_count` int(11) NOT NULL DEFAULT '0' COMMENT 'Импортировано новых',
    `updated_count` int(11) NOT NULL DEFAULT '0' COMMENT 'Обновлено существующих',
    `failed_count` int(11) NOT NULL DEFAULT '0' COMMENT 'Ошибок',
    `duplicate_count` int(11) NOT NULL DEFAULT '0' COMMENT 'Дубликатов найдено',
    `started_at` datetime DEFAULT NULL COMMENT 'Время старта',
    `finished_at` datetime DEFAULT NULL COMMENT 'Время завершения',
    `duration_seconds` int(11) DEFAULT NULL COMMENT 'Длительность в секундах',
    `config` text COMMENT 'JSON с параметрами запуска',
    `error_message` text COMMENT 'Сообщение об ошибке',
    `created_by` int(11) DEFAULT NULL COMMENT 'Кто запустил',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_import_task_source_id` (`source_id`),
    KEY `idx_import_task_status` (`status`),
    KEY `idx_import_task_created_at` (`created_at`),
    CONSTRAINT `fk_import_task_source_id` FOREIGN KEY (`source_id`) REFERENCES `import_source` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Таблица логов импорта
CREATE TABLE IF NOT EXISTS `import_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `task_id` int(11) NOT NULL COMMENT 'ID задачи',
    `product_id` int(11) DEFAULT NULL COMMENT 'ID товара',
    `action` varchar(20) NOT NULL COMMENT 'Действие (created, updated, skipped, error)',
    `level` varchar(20) NOT NULL DEFAULT 'info' COMMENT 'Уровень (info, warning, error)',
    `sku` varchar(100) DEFAULT NULL COMMENT 'SKU товара',
    `poizon_id` varchar(100) DEFAULT NULL COMMENT 'ID в Poizon',
    `product_name` varchar(255) DEFAULT NULL COMMENT 'Название товара',
    `message` text COMMENT 'Сообщение лога',
    `data` text COMMENT 'JSON с данными',
    `error_details` text COMMENT 'Детали ошибки',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_import_log_task_id` (`task_id`),
    KEY `idx_import_log_product_id` (`product_id`),
    KEY `idx_import_log_action` (`action`),
    KEY `idx_import_log_created_at` (`created_at`),
    CONSTRAINT `fk_import_log_task_id` FOREIGN KEY (`task_id`) REFERENCES `import_task` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_import_log_product_id` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Таблица цен товаров из разных источников
CREATE TABLE IF NOT EXISTS `import_product_price` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) DEFAULT NULL COMMENT 'ID товара',
    `source_id` int(11) NOT NULL COMMENT 'ID источника',
    `external_sku` varchar(100) NOT NULL COMMENT 'SKU товара в источнике',
    `price_original` decimal(10, 2) NOT NULL COMMENT 'Цена в оригинальной валюте',
    `price_byn` decimal(10, 2) DEFAULT NULL COMMENT 'Цена в BYN',
    `currency_code` varchar(3) NOT NULL COMMENT 'Код валюты',
    `exchange_rate` decimal(10, 6) DEFAULT NULL COMMENT 'Курс конвертации',
    `size` varchar(50) DEFAULT NULL COMMENT 'Размер товара',
    `is_available` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Доступен ли товар',
    `url` varchar(500) DEFAULT NULL COMMENT 'URL товара в источнике',
    `parsed_at` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Время парсинга',
    PRIMARY KEY (`id`),
    KEY `idx_import_product_price_product_id` (`product_id`),
    KEY `idx_import_product_price_source_id` (`source_id`),
    KEY `idx_import_product_price_external_sku` (`external_sku`),
    KEY `idx_import_product_price_size` (`size`),
    KEY `idx_import_product_price_is_available` (`is_available`),
    CONSTRAINT `fk_import_product_price_product_id` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_import_product_price_source_id` FOREIGN KEY (`source_id`) REFERENCES `import_source` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Таблица маппинга категорий
CREATE TABLE IF NOT EXISTS `import_category_map` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `source_id` int(11) NOT NULL COMMENT 'ID источника',
    `source_category_name` varchar(255) NOT NULL COMMENT 'Название категории в источнике',
    `source_category_url` varchar(500) DEFAULT NULL COMMENT 'URL категории в источнике',
    `category_id` int(11) DEFAULT NULL COMMENT 'ID категории в нашей системе',
    `is_auto_mapped` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Автоматическое сопоставление',
    `priority` int(11) NOT NULL DEFAULT '0' COMMENT 'Приоритет (обувь = высокий)',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_import_category_map_source_id` (`source_id`),
    KEY `idx_import_category_map_category_id` (`category_id`),
    CONSTRAINT `fk_import_category_map_source_id` FOREIGN KEY (`source_id`) REFERENCES `import_source` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_import_category_map_category_id` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Таблица курсов валют
CREATE TABLE IF NOT EXISTS `currency_rate` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `currency_code` varchar(3) NOT NULL COMMENT 'Код валюты',
    `rate_to_byn` decimal(10, 6) NOT NULL COMMENT 'Курс к BYN',
    `rate_date` date NOT NULL COMMENT 'Дата курса',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_currency_rate_code_date` (`currency_code`, `rate_date`),
    KEY `idx_currency_rate_currency_code` (`currency_code`),
    KEY `idx_currency_rate_rate_date` (`rate_date`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Таблица уведомлений об импорте
CREATE TABLE IF NOT EXISTS `import_notification` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `task_id` int(11) NOT NULL COMMENT 'ID задачи импорта',
    `type` varchar(20) NOT NULL COMMENT 'Тип уведомления (success, error, warning)',
    `title` varchar(255) NOT NULL COMMENT 'Заголовок',
    `message` text NOT NULL COMMENT 'Сообщение',
    `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Прочитано',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_import_notification_task_id` (`task_id`),
    KEY `idx_import_notification_is_read` (`is_read`),
    KEY `idx_import_notification_created_at` (`created_at`),
    CONSTRAINT `fk_import_notification_task_id` FOREIGN KEY (`task_id`) REFERENCES `import_task` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Добавляем поля в существующую таблицу product (проверяем существование)
SET
    @sql = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE
                        TABLE_SCHEMA = 'order_management'
                        AND TABLE_NAME = 'product'
                        AND COLUMN_NAME = 'import_source_id'
                ) > 0, 'SELECT 1', 'ALTER TABLE `product` ADD COLUMN `import_source_id` int(11) DEFAULT NULL COMMENT "ID источника импорта"'
            )
    );

PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

SET
    @sql = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE
                        TABLE_SCHEMA = 'order_management'
                        AND TABLE_NAME = 'product'
                        AND COLUMN_NAME = 'external_sku'
                ) > 0, 'SELECT 1', 'ALTER TABLE `product` ADD COLUMN `external_sku` varchar(100) DEFAULT NULL COMMENT "SKU во внешней системе"'
            )
    );

PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

SET
    @sql = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE
                        TABLE_SCHEMA = 'order_management'
                        AND TABLE_NAME = 'product'
                        AND COLUMN_NAME = 'best_price_source_id'
                ) > 0, 'SELECT 1', 'ALTER TABLE `product` ADD COLUMN `best_price_source_id` int(11) DEFAULT NULL COMMENT "ID источника с лучшей ценой"'
            )
    );

PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

SET
    @sql = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE
                        TABLE_SCHEMA = 'order_management'
                        AND TABLE_NAME = 'product'
                        AND COLUMN_NAME = 'last_import_at'
                ) > 0, 'SELECT 1', 'ALTER TABLE `product` ADD COLUMN `last_import_at` datetime DEFAULT NULL COMMENT "Последний импорт"'
            )
    );

PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;

-- Вставляем начальные данные для источников
INSERT IGNORE INTO
    `import_source` (
        `name`,
        `code`,
        `base_url`,
        `currency_code`
    )
VALUES (
        'Lamoda',
        'lamoda',
        'https://www.lamoda.by',
        'BYN'
    ),
    (
        'Dewu (Poizon)',
        'dewu',
        'https://www.dewu.com',
        'CNY'
    ),
    (
        'Zalando',
        'zalando',
        'https://www.zalando.com',
        'EUR'
    ),
    (
        'StockX',
        'stockx',
        'https://stockx.com',
        'USD'
    );