<?php

use yii\db\Migration;

/**
 * Добавление тестовых заказов для проверки wizard интерфейса
 */
class m260325_064616_add_test_orders extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $time = time();
        
        // Тестовые заказы с разными статусами и данными
        $testOrders = [
            [
                'order_number' => 'TEST-001',
                'client_name' => 'Иванов Иван Иванович',
                'client_phone' => '+375291234567',
                'client_email' => 'ivanov.test@example.com',
                'delivery_method' => 'Dobropost',
                'delivery_date' => date('Y-m-d', strtotime('+7 days')),
                'delivery_country' => 'Беларусь',
                'city' => 'Минск',
                'full_address' => 'ул. Независимости 123, кв. 45',
                'status' => 'created',
                'total_amount' => 1250.50,
                'product_price' => 1200.00,
                'logistics_price' => 35.50,
                'commission_price' => 15.00,
                'delivery_cost' => 0.00,
                'created_by' => 1,
                'created_at' => $time - 86400 * 5, // 5 дней назад
                'updated_at' => $time - 86400 * 5,
                'token' => Yii::$app->security->generateRandomString(32),
            ],
            [
                'order_number' => 'TEST-002',
                'client_name' => 'Петров Петр Петрович',
                'client_phone' => '+375337654321',
                'client_email' => 'petrov.test@example.com',
                'delivery_method' => 'EMS',
                'delivery_date' => date('Y-m-d', strtotime('+10 days')),
                'delivery_country' => 'Беларусь',
                'city' => 'Гродно',
                'full_address' => 'ул. Советская 45, кв. 12',
                'status' => 'confirmed',
                'total_amount' => 890.00,
                'product_price' => 850.00,
                'logistics_price' => 25.00,
                'commission_price' => 15.00,
                'delivery_cost' => 0.00,
                'created_by' => 1,
                'created_at' => $time - 86400 * 3, // 3 дня назад
                'updated_at' => $time - 86400 * 2,
                'token' => Yii::$app->security->generateRandomString(32),
            ],
            [
                'order_number' => 'TEST-003',
                'client_name' => 'Сидорова Анна Михайловна',
                'client_phone' => '+375449876543',
                'client_email' => 'sidorova.test@example.com',
                'delivery_method' => 'Самовывоз',
                'delivery_date' => date('Y-m-d', strtotime('+3 days')),
                'delivery_country' => 'Беларусь',
                'city' => 'Витебск',
                'full_address' => 'ул. Ленина 78, кв. 34',
                'status' => 'paid',
                'total_amount' => 2100.75,
                'product_price' => 2000.00,
                'logistics_price' => 50.75,
                'commission_price' => 50.00,
                'delivery_cost' => 0.00,
                'created_by' => 1,
                'created_at' => $time - 86400 * 7, // 7 дней назад
                'updated_at' => $time - 86400 * 1,
                'token' => Yii::$app->security->generateRandomString(32),
            ],
            [
                'order_number' => 'TEST-004',
                'client_name' => 'Козлов Дмитрий Сергеевич',
                'client_phone' => '+375295432109',
                'client_email' => 'kozlov.test@example.com',
                'delivery_method' => 'Белпочта',
                'delivery_date' => date('Y-m-d', strtotime('+14 days')),
                'delivery_country' => 'Беларусь',
                'city' => 'Брест',
                'full_address' => 'пр. Мира 234, кв. 56',
                'status' => 'shipped',
                'total_amount' => 450.25,
                'product_price' => 400.00,
                'logistics_price' => 30.25,
                'commission_price' => 20.00,
                'delivery_cost' => 0.00,
                'created_by' => 1,
                'created_at' => $time - 86400 * 10, // 10 дней назад
                'updated_at' => $time - 86400 * 4,
                'token' => Yii::$app->security->generateRandomString(32),
            ],
            [
                'order_number' => 'TEST-005',
                'client_name' => 'Морозова Елена Александровна',
                'client_phone' => '+375331234567',
                'client_email' => 'morozova.test@example.com',
                'delivery_method' => 'СДЭК',
                'delivery_date' => date('Y-m-d', strtotime('+5 days')),
                'delivery_country' => 'Беларусь',
                'city' => 'Могилев',
                'full_address' => 'ул. Первомайская 67, кв. 89',
                'status' => 'delivered',
                'total_amount' => 1750.00,
                'product_price' => 1650.00,
                'logistics_price' => 45.00,
                'commission_price' => 55.00,
                'delivery_cost' => 0.00,
                'created_by' => 1,
                'created_at' => $time - 86400 * 14, // 14 дней назад
                'updated_at' => $time - 86400 * 6,
                'token' => Yii::$app->security->generateRandomString(32),
            ],
        ];

        // Вставляем заказы
        $this->batchInsert('order', [
            'order_number', 'client_name', 'client_phone', 'client_email',
            'delivery_method', 'delivery_date', 'delivery_country', 'city',
            'full_address', 'status', 'total_amount', 'product_price',
            'logistics_price', 'commission_price', 'delivery_cost',
            'created_by', 'created_at', 'updated_at', 'token'
        ], $testOrders);

        // Получаем ID вставленных заказов
        $orderIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $orderIds[] = $this->db->getLastInsertID() - (5 - $i);
        }

        // Добавляем товары для каждого заказа
        $orderItems = [];

        // Товары для заказа 1
        $orderItems[] = [
            'order_id' => $orderIds[0],
            'product_name' => 'Nike Air Jordan 1 Retro High OG',
            'quantity' => 1,
            'price' => 1200.00,
            'total' => 1200.00,
            'created_at' => $time - 86400 * 5,
        ];

        // Товары для заказа 2
        $orderItems[] = [
            'order_id' => $orderIds[1],
            'product_name' => 'Adidas Yeezy Boost 350 V2',
            'quantity' => 1,
            'price' => 850.00,
            'total' => 850.00,
            'created_at' => $time - 86400 * 3,
        ];

        // Товары для заказа 3
        $orderItems[] = [
            'order_id' => $orderIds[2],
            'product_name' => 'New Balance 550 White Green',
            'quantity' => 2,
            'price' => 1000.00,
            'total' => 2000.00,
            'created_at' => $time - 86400 * 7,
        ];

        // Товары для заказа 4
        $orderItems[] = [
            'order_id' => $orderIds[3],
            'product_name' => 'Converse Chuck Taylor All Star 70',
            'quantity' => 1,
            'price' => 400.00,
            'total' => 400.00,
            'created_at' => $time - 86400 * 10,
        ];

        // Товары для заказа 5
        $orderItems[] = [
            'order_id' => $orderIds[4],
            'product_name' => 'Vans Old Skool Black/White',
            'quantity' => 1,
            'price' => 1650.00,
            'total' => 1650.00,
            'created_at' => $time - 86400 * 14,
        ];

        // Вставляем товары
        $this->batchInsert('order_item', [
            'order_id', 'product_name', 'quantity', 'price', 'total', 'created_at'
        ], $orderItems);

        // Добавляем историю изменений для некоторых заказов
        $orderHistory = [];
        
        // История для заказа 2 (confirmed)
        $orderHistory[] = [
            'order_id' => $orderIds[1],
            'old_status' => 'created',
            'new_status' => 'confirmed',
            'changed_by' => 1,
            'comment' => 'Заказ подтвержден менеджером',
            'created_at' => $time - 86400 * 2,
        ];

        // История для заказа 3 (paid)
        $orderHistory[] = [
            'order_id' => $orderIds[2],
            'old_status' => 'created',
            'new_status' => 'confirmed',
            'changed_by' => 1,
            'comment' => 'Заказ подтвержден',
            'created_at' => $time - 86400 * 5,
        ];
        $orderHistory[] = [
            'order_id' => $orderIds[2],
            'old_status' => 'confirmed',
            'new_status' => 'paid',
            'changed_by' => 1,
            'comment' => 'Оплата получена',
            'created_at' => $time - 86400 * 1,
        ];

        // История для заказа 4 (shipped)
        $orderHistory[] = [
            'order_id' => $orderIds[3],
            'old_status' => 'created',
            'new_status' => 'confirmed',
            'changed_by' => 1,
            'comment' => 'Заказ подтвержден',
            'created_at' => $time - 86400 * 8,
        ];
        $orderHistory[] = [
            'order_id' => $orderIds[3],
            'old_status' => 'confirmed',
            'new_status' => 'paid',
            'changed_by' => 1,
            'comment' => 'Оплата получена',
            'created_at' => $time - 86400 * 6,
        ];
        $orderHistory[] = [
            'order_id' => $orderIds[3],
            'old_status' => 'paid',
            'new_status' => 'shipped',
            'changed_by' => 1,
            'comment' => 'Заказ отправлен, трек-номер: CN123456789',
            'created_at' => $time - 86400 * 4,
        ];

        // История для заказа 5 (delivered)
        $orderHistory[] = [
            'order_id' => $orderIds[4],
            'old_status' => 'created',
            'new_status' => 'confirmed',
            'changed_by' => 1,
            'comment' => 'Заказ подтвержден',
            'created_at' => $time - 86400 * 12,
        ];
        $orderHistory[] = [
            'order_id' => $orderIds[4],
            'old_status' => 'confirmed',
            'new_status' => 'paid',
            'changed_by' => 1,
            'comment' => 'Оплата получена',
            'created_at' => $time - 86400 * 10,
        ];
        $orderHistory[] = [
            'order_id' => $orderIds[4],
            'old_status' => 'paid',
            'new_status' => 'shipped',
            'changed_by' => 1,
            'comment' => 'Заказ отправлен',
            'created_at' => $time - 86400 * 8,
        ];
        $orderHistory[] = [
            'order_id' => $orderIds[4],
            'old_status' => 'shipped',
            'new_status' => 'delivered',
            'changed_by' => 1,
            'comment' => 'Заказ доставлен клиенту',
            'created_at' => $time - 86400 * 6,
        ];

        // Вставляем историю
        $this->batchInsert('order_history', [
            'order_id', 'old_status', 'new_status', 'changed_by', 'comment', 'created_at'
        ], $orderHistory);

        echo "Добавлено 5 тестовых заказов с товарами и историей изменений\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Удаляем тестовые заказы
        $this->delete('order_history', ['order_id' => [
            'SELECT id FROM order WHERE order_number LIKE "TEST-%"'
        ]]);
        $this->delete('order_item', ['order_id' => [
            'SELECT id FROM order WHERE order_number LIKE "TEST-%"'
        ]]);
        $this->delete('order', ['order_number LIKE "TEST-%"']);

        echo "Тестовые заказы удалены\n";
    }
}
