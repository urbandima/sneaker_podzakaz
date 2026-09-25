<?php

return [
    // CMP-464: были sneakerculture.by (CMP-285 — домен не боевой, вообще не тот сайт)
    // и sneakerhead.by без дефиса (резолвится на другой IP, HTTPS зависает по
    // timeout — не боевой). Реальный прод подтверждён живым HTTP-запросом
    // (200 на главной, брендинг совпадает, тот же IP, что и у sneakerculture.by
    // на общем хостинге) — sneaker-head.by, ЧЕРЕЗ ДЕФИС.
    'adminEmail' => 'admin@sneaker-head.by',
    'senderEmail' => 'noreply@sneaker-head.by',
    'senderName' => 'СНИКЕРХЭД',
    'frontendUrl'     => env('FRONTEND_URL', 'https://sneaker-head.by'),
    'frontendBaseUrl' => env('FRONTEND_URL', 'https://sneaker-head.by'),

    'socialAuth' => [
        'googleClientId' => env('GOOGLE_CLIENT_ID'),
        'googleClientSecret' => env('GOOGLE_CLIENT_SECRET'),
        'yandexClientId' => env('YANDEX_CLIENT_ID'),
        'yandexClientSecret' => env('YANDEX_CLIENT_SECRET'),
        'telegramClientId' => env('TELEGRAM_CLIENT_ID'),
        'telegramClientSecret' => env('TELEGRAM_CLIENT_SECRET'),
    ],

    // Статусы заказов
    'orderStatuses' => [
        'created' => 'Заказ составлен',
        'paid' => 'Заказ оплачен',
        'accepted' => 'Заказ принят',
        'ordered' => 'Заказан товар',
        'received' => 'Заказ получен',
        'issued' => 'Заказ выдан',
    ],

    // Статусы, которые может изменять логист
    'logistStatuses' => [
        'ordered' => 'Заказан товар',
        'received' => 'Заказ получен',
        'issued' => 'Заказ выдан',
    ],

    // Тестовые реквизиты ООО "СНИКЕРХЭД"
    'companyDetails' => [
        'name' => 'ООО "СНИКЕРХЭД"',
        'unp' => '123456789',
        'address' => 'г. Минск, ул. Тестовая, д. 1',
        'bank' => 'ОАО "Тестовый банк"',
        'bic' => 'TESTBY2X',
        'account' => 'BY12TEST12345678901234567890',
        'phone' => '+375 29 123-45-67',
        'email' => 'info@sneaker-head.by',
    ],

    // Настройки Poizon/Dewu API
    'poizonApiUrl' => 'https://api.poizon-parser.com/v1', // Замените на реальный URL API
    'poizonApiKey' => null, // Замените на ваш API ключ (если требуется)
    'poizonXmlUrl' => null, // URL XML фида (если используется), например: 'https://s3.q-parser.ru/export/xxx/poizon.xml'

    // Курс CNY -> BYN (обновляется вручную или через API курсов)
    'cnyToBynRate' => 0.45, // 1 CNY ≈ 0.45 BYN (примерный курс)

    // Настройки генерации Sitemap
    'sitemap' => [
        'filterMaxPages' => 5000,          // Максимум фильтрованных страниц
        'filterMinResults' => 5,           // Минимум результатов для включения в sitemap
        'productBatchSize' => 500,         // Размер батча для товаров
        'imageBatchSize' => 500,           // Размер батча для изображений
        'imageLimit' => 5,                 // Лимит изображений на товар
        'topProductsForImages' => 1000,    // Топ товаров для image sitemap
        'lockTtl' => 600,                  // Время блокировки генерации (секунды)
        'cacheCheckInterval' => 60,        // Интервал проверки флага pending (секунды)
        'enableValidation' => defined('YII_ENV_DEV') && YII_ENV_DEV, // Валидация XML (только в dev)
        'changefreqHigh' => 'daily',       // Частота обновления популярных страниц
        'changefreqLow' => 'weekly',       // Частота обновления редких страниц
    ],

    // Настройки доставки
    'shipping' => require __DIR__ . '/shipping.php',
];
