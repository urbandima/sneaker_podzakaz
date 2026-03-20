<?php

/**
 * Конфигурация методов доставки
 * 
 * Этот файл содержит настройки всех доступных методов доставки,
 * их стоимость, условия бесплатной доставки и зоны покрытия.
 */

return [
    'shipping' => [
        'methods' => [
            'courier_minsk' => [
                'name' => 'Курьер по Минску',
                'description' => 'Доставка курьером в пределах Минска и пригорода',
                'baseCost' => 10,
                'freeFrom' => 100, // Бесплатная доставка от 100 BYN
                'estimatedDays' => 1,
                'countries' => ['belarus'],
                'enabled' => true,
            ],
            'pickup_minsk' => [
                'name' => 'Самовывоз (Минск)',
                'description' => 'Самовывоз из пункта выдачи в Минске, ул. Ленина 10',
                'baseCost' => 0,
                'estimatedDays' => 1,
                'countries' => ['belarus'],
                'enabled' => true,
            ],
            'europochta' => [
                'name' => 'Европочта',
                'description' => 'Доставка службой Европочта по всей Беларуси',
                'baseCost' => 5,
                'freeFrom' => 80,
                'estimatedDays' => 3,
                'countries' => ['belarus'],
                'enabled' => true,
            ],
            'belpochta' => [
                'name' => 'Белпочта',
                'description' => 'Доставка почтой Беларуси',
                'baseCost' => 4,
                'freeFrom' => 70,
                'estimatedDays' => 5,
                'countries' => ['belarus'],
                'enabled' => true,
            ],
            'sdek' => [
                'name' => 'СДЭК',
                'description' => 'Доставка транспортной компанией СДЭК (Беларусь, Россия)',
                'baseCost' => 8,
                'costPerKg' => 2, // Дополнительная стоимость за каждый кг свыше 1 кг
                'freeFrom' => 120,
                'estimatedDays' => 3,
                'countries' => ['belarus', 'russia'],
                'enabled' => true,
            ],
        ],
        
        // Зоны курьерской доставки по Минску
        'courierZones' => [
            'center' => [
                'name' => 'Центр Минска',
                'cost' => 5,
                'freeFrom' => 100,
                'districts' => ['Центральный', 'Советский'],
            ],
            'suburbs' => [
                'name' => 'Пригород Минска',
                'cost' => 10,
                'freeFrom' => 150,
                'districts' => ['Заводской', 'Партизанский', 'Фрунзенский', 'Ленинский', 'Октябрьский', 'Московский'],
            ],
            'outside' => [
                'name' => 'За пределами МКАД',
                'cost' => 15,
                'freeFrom' => 200,
            ],
        ],
        
        // Настройки по умолчанию
        'default' => [
            'country' => 'belarus',
            'method' => 'courier_minsk',
            'currency' => 'BYN',
        ],
    ],
];
