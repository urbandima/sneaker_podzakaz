<?php

use yii\db\Migration;

/**
 * Добавление тестовых данных для проверки функционала
 */
class m260330_120000_add_test_data extends Migration
{
    public function safeUp()
    {
        // Тестовые клиенты
        $this->batchInsert('customer', ['first_name', 'last_name', 'email', 'phone', 'status', 'loyalty_points', 'created_at', 'updated_at'], [
            ['Иван', 'Петров', 'ivan.petrov@example.com', '+375291234567', 'active', 150, time(), time()],
            ['Мария', 'Сидорова', 'maria.sidorova@example.com', '+375297654321', 'active', 320, time(), time()],
            ['Алексей', 'Козлов', 'alex.kozlov@example.com', '+375293456789', 'active', 80, time(), time()],
            ['Ольга', 'Новикова', 'olga.novikova@example.com', '+375295678901', 'active', 500, time(), time()],
            ['Дмитрий', 'Волков', 'dmitry.volkov@example.com', '+375298765432', 'active', 45, time(), time()],
        ]);

        // Тестовые товары
        $this->batchInsert('product', [
            'name', 'slug', 'description', 'price', 'price_cny', 'category_id', 
            'brand', 'is_active', 'stock_quantity', 'created_at', 'updated_at'
        ], [
            [
                'Nike Air Max 90', 
                'nike-air-max-90', 
                'Классические кроссовки Nike Air Max 90 с видимой амортизацией Air',
                450.00, 
                1200.00,
                1,
                'Nike',
                1,
                15,
                time(),
                time()
            ],
            [
                'Adidas Yeezy Boost 350', 
                'adidas-yeezy-boost-350', 
                'Культовые кроссовки от Kanye West с технологией Boost',
                890.00, 
                2400.00,
                1,
                'Adidas',
                1,
                8,
                time(),
                time()
            ],
            [
                'Jordan 1 Retro High', 
                'jordan-1-retro-high', 
                'Легендарные баскетбольные кроссовки Air Jordan 1',
                650.00, 
                1800.00,
                1,
                'Jordan',
                1,
                12,
                time(),
                time()
            ],
            [
                'New Balance 574', 
                'new-balance-574', 
                'Классические кроссовки New Balance 574 в ретро стиле',
                320.00, 
                900.00,
                1,
                'New Balance',
                1,
                20,
                time(),
                time()
            ],
            [
                'Puma Suede Classic', 
                'puma-suede-classic', 
                'Культовые замшевые кроссовки Puma Suede',
                280.00, 
                750.00,
                1,
                'Puma',
                1,
                18,
                time(),
                time()
            ],
        ]);

        // Тестовые заказы
        $orders = [
            [
                'customer_id' => 1,
                'client_name' => 'Иван Петров',
                'client_phone' => '+375291234567',
                'client_email' => 'ivan.petrov@example.com',
                'status' => 'new',
                'total_amount' => 450.00,
                'address' => 'г. Минск, ул. Ленина, д. 10, кв. 5',
                'delivery_method' => 'Европочта',
                'payment_method' => 'Наличные',
                'payment_status' => 0,
                'token' => bin2hex(random_bytes(16)),
                'created_at' => time() - 3600,
                'updated_at' => time() - 3600,
            ],
            [
                'customer_id' => 2,
                'client_name' => 'Мария Сидорова',
                'client_phone' => '+375297654321',
                'client_email' => 'maria.sidorova@example.com',
                'status' => 'paid',
                'total_amount' => 890.00,
                'address' => 'г. Минск, пр. Независимости, д. 25, кв. 12',
                'delivery_method' => 'СДЭК',
                'payment_method' => 'Онлайн',
                'payment_status' => 1,
                'paid_amount' => 890.00,
                'token' => bin2hex(random_bytes(16)),
                'created_at' => time() - 86400,
                'updated_at' => time() - 7200,
            ],
            [
                'customer_id' => 3,
                'client_name' => 'Алексей Козлов',
                'client_phone' => '+375293456789',
                'client_email' => 'alex.kozlov@example.com',
                'status' => 'ordered',
                'total_amount' => 650.00,
                'address' => 'г. Гомель, ул. Советская, д. 45',
                'delivery_method' => 'Белпочта',
                'payment_method' => 'Онлайн',
                'payment_status' => 1,
                'paid_amount' => 650.00,
                'token' => bin2hex(random_bytes(16)),
                'created_at' => time() - 172800,
                'updated_at' => time() - 86400,
            ],
            [
                'customer_id' => 4,
                'client_name' => 'Ольга Новикова',
                'client_phone' => '+375295678901',
                'client_email' => 'olga.novikova@example.com',
                'status' => 'warehouse',
                'total_amount' => 320.00,
                'address' => 'г. Брест, ул. Московская, д. 78, кв. 3',
                'delivery_method' => 'Европочта',
                'payment_method' => 'Онлайн',
                'payment_status' => 1,
                'paid_amount' => 320.00,
                'track_number' => 'BY123456789',
                'token' => bin2hex(random_bytes(16)),
                'created_at' => time() - 259200,
                'updated_at' => time() - 3600,
            ],
            [
                'customer_id' => 5,
                'client_name' => 'Дмитрий Волков',
                'client_phone' => '+375298765432',
                'client_email' => 'dmitry.volkov@example.com',
                'status' => 'completed',
                'total_amount' => 280.00,
                'address' => 'г. Витебск, ул. Ленина, д. 12',
                'delivery_method' => 'Самовывоз',
                'payment_method' => 'Наличные',
                'payment_status' => 1,
                'paid_amount' => 280.00,
                'token' => bin2hex(random_bytes(16)),
                'created_at' => time() - 604800,
                'updated_at' => time() - 86400,
            ],
        ];

        foreach ($orders as $order) {
            $this->insert('order', $order);
        }

        // Товары в заказах
        $this->batchInsert('order_item', ['order_id', 'product_id', 'product_name', 'size', 'quantity', 'price'], [
            [1, 1, 'Nike Air Max 90', '42', 1, 450.00],
            [2, 2, 'Adidas Yeezy Boost 350', '43', 1, 890.00],
            [3, 3, 'Jordan 1 Retro High', '44', 1, 650.00],
            [4, 4, 'New Balance 574', '41', 1, 320.00],
            [5, 5, 'Puma Suede Classic', '42', 1, 280.00],
        ]);

        // История изменений заказов
        $this->batchInsert('order_history', ['order_id', 'status_from', 'status_to', 'comment', 'created_by', 'created_at'], [
            [2, 'new', 'paid', 'Оплата подтверждена', 1, time() - 7200],
            [3, 'paid', 'ordered', 'Товар заказан у поставщика', 1, time() - 86400],
            [4, 'ordered', 'warehouse', 'Товар прибыл на склад', 1, time() - 3600],
            [5, 'warehouse', 'completed', 'Клиент получил заказ', 1, time() - 86400],
        ]);

        // Настройки компании
        $this->insert('company_settings', [
            'name' => 'СНИКЕРХЭД',
            'email' => 'info@sneakerhead.by',
            'phone' => '+375291234567',
            'address' => 'г. Минск, ул. Ленина, д. 1',
            'unp' => '123456789',
            'bank_details' => 'BY12 ALFA 1234 5678 9012 3456 7890',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        // Курс валют
        $this->insert('exchange_rate', [
            'currency' => 'CNY',
            'rate' => 0.28,
            'source' => 'NBRB',
            'updated_at' => time(),
        ]);

        return true;
    }

    public function safeDown()
    {
        $this->delete('order_history');
        $this->delete('order_item');
        $this->delete('order');
        $this->delete('product');
        $this->delete('customer');
        $this->delete('company_settings');
        $this->delete('exchange_rate');

        return true;
    }
}
