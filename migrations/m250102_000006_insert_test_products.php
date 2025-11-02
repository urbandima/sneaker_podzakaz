<?php

use yii\db\Migration;

/**
 * Добавление тестовых товаров с новыми полями
 */
class m250102_000006_insert_test_products extends Migration
{
    public function safeUp()
    {
        // Добавляем тестовые товары с новыми полями фильтров
        
        // Nike Air Max 90
        $this->insert('{{%product}}', [
            'category_id' => 1,
            'brand_id' => 1,
            'name' => 'Nike Air Max 90',
            'slug' => 'nike-air-max-90',
            'description' => 'Легендарные кроссовки Nike Air Max 90 с культовым дизайном. Видимая амортизация Air-Sole обеспечивает комфорт на весь день.',
            'price' => 189.00,
            'old_price' => 249.00,
            'main_image' => '/img/products/nike-air-max-90.jpg',
            'is_active' => 1,
            'is_featured' => 1,
            'stock_status' => 'in_stock',
            // Новые поля
            'material' => 'leather',
            'season' => 'all',
            'gender' => 'unisex',
            'height' => 'low',
            'fastening' => 'laces',
            'country' => 'vietnam',
            'has_bonus' => 0,
            'promo_2for1' => 0,
            'is_exclusive' => 0,
            'rating' => 4.7,
            'reviews_count' => 234,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        // Adidas Ultraboost 21
        $this->insert('{{%product}}', [
            'category_id' => 1,
            'brand_id' => 2,
            'name' => 'Adidas Ultraboost 21',
            'slug' => 'adidas-ultraboost-21',
            'description' => 'Революционные беговые кроссовки с технологией Boost. Невероятная амортизация и энергетический возврат.',
            'price' => 210.00,
            'old_price' => null,
            'main_image' => '/img/products/adidas-ultraboost-21.jpg',
            'is_active' => 1,
            'is_featured' => 1,
            'stock_status' => 'in_stock',
            'material' => 'textile',
            'season' => 'all',
            'gender' => 'male',
            'height' => 'low',
            'fastening' => 'laces',
            'country' => 'vietnam',
            'has_bonus' => 1,
            'promo_2for1' => 0,
            'is_exclusive' => 0,
            'rating' => 4.9,
            'reviews_count' => 567,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        // New Balance 574
        $this->insert('{{%product}}', [
            'category_id' => 1,
            'brand_id' => 3,
            'name' => 'New Balance 574',
            'slug' => 'new-balance-574',
            'description' => 'Классические кроссовки New Balance 574. Ретро-стиль и современный комфорт в одной модели.',
            'price' => 145.00,
            'old_price' => 179.00,
            'main_image' => '/img/products/new-balance-574.jpg',
            'is_active' => 1,
            'is_featured' => 0,
            'stock_status' => 'in_stock',
            'material' => 'suede',
            'season' => 'demi',
            'gender' => 'unisex',
            'height' => 'low',
            'fastening' => 'laces',
            'country' => 'usa',
            'has_bonus' => 0,
            'promo_2for1' => 1,
            'is_exclusive' => 0,
            'rating' => 4.5,
            'reviews_count' => 189,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        // Puma RS-X
        $this->insert('{{%product}}', [
            'category_id' => 1,
            'brand_id' => 4,
            'name' => 'Puma RS-X',
            'slug' => 'puma-rs-x',
            'description' => 'Футуристичные кроссовки Puma RS-X. Яркий дизайн и максимальный комфорт для городской жизни.',
            'price' => 159.00,
            'old_price' => 199.00,
            'main_image' => '/img/products/puma-rs-x.jpg',
            'is_active' => 1,
            'is_featured' => 1,
            'stock_status' => 'in_stock',
            'material' => 'synthetic',
            'season' => 'summer',
            'gender' => 'unisex',
            'height' => 'low',
            'fastening' => 'laces',
            'country' => 'china',
            'has_bonus' => 0,
            'promo_2for1' => 0,
            'is_exclusive' => 1,
            'rating' => 4.3,
            'reviews_count' => 98,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        // Converse Chuck Taylor All Star
        $this->insert('{{%product}}', [
            'category_id' => 1,
            'brand_id' => 5,
            'name' => 'Converse Chuck Taylor All Star',
            'slug' => 'converse-chuck-taylor-all-star',
            'description' => 'Легендарные кеды Converse Chuck Taylor. Классика, которая никогда не выходит из моды.',
            'price' => 89.00,
            'old_price' => null,
            'main_image' => '/img/products/converse-chuck-taylor.jpg',
            'is_active' => 1,
            'is_featured' => 0,
            'stock_status' => 'in_stock',
            'material' => 'canvas',
            'season' => 'summer',
            'gender' => 'unisex',
            'height' => 'high',
            'fastening' => 'laces',
            'country' => 'vietnam',
            'has_bonus' => 0,
            'promo_2for1' => 0,
            'is_exclusive' => 0,
            'rating' => 4.8,
            'reviews_count' => 1245,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        // Vans Old Skool
        $this->insert('{{%product}}', [
            'category_id' => 1,
            'brand_id' => 6,
            'name' => 'Vans Old Skool',
            'slug' => 'vans-old-skool',
            'description' => 'Культовые кеды Vans Old Skool. Идеальны для скейтбординга и повседневной носки.',
            'price' => 99.00,
            'old_price' => 119.00,
            'main_image' => '/img/products/vans-old-skool.jpg',
            'is_active' => 1,
            'is_featured' => 1,
            'stock_status' => 'in_stock',
            'material' => 'canvas',
            'season' => 'all',
            'gender' => 'unisex',
            'height' => 'low',
            'fastening' => 'laces',
            'country' => 'china',
            'has_bonus' => 1,
            'promo_2for1' => 0,
            'is_exclusive' => 0,
            'rating' => 4.6,
            'reviews_count' => 678,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        // Reebok Classic Leather
        $this->insert('{{%product}}', [
            'category_id' => 1,
            'brand_id' => 7,
            'name' => 'Reebok Classic Leather',
            'slug' => 'reebok-classic-leather',
            'description' => 'Классические кроссовки Reebok из натуральной кожи. Минималистичный дизайн и превосходное качество.',
            'price' => 129.00,
            'old_price' => 159.00,
            'main_image' => '/img/products/reebok-classic-leather.jpg',
            'is_active' => 1,
            'is_featured' => 0,
            'stock_status' => 'in_stock',
            'material' => 'leather',
            'season' => 'all',
            'gender' => 'unisex',
            'height' => 'low',
            'fastening' => 'laces',
            'country' => 'indonesia',
            'has_bonus' => 0,
            'promo_2for1' => 1,
            'is_exclusive' => 0,
            'rating' => 4.4,
            'reviews_count' => 345,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        // Asics Gel-Kayano 28
        $this->insert('{{%product}}', [
            'category_id' => 1,
            'brand_id' => 8,
            'name' => 'Asics Gel-Kayano 28',
            'slug' => 'asics-gel-kayano-28',
            'description' => 'Профессиональные беговые кроссовки с гелевой амортизацией. Максимальная поддержка и стабильность.',
            'price' => 235.00,
            'old_price' => null,
            'main_image' => '/img/products/asics-gel-kayano-28.jpg',
            'is_active' => 1,
            'is_featured' => 1,
            'stock_status' => 'in_stock',
            'material' => 'mesh',
            'season' => 'all',
            'gender' => 'male',
            'height' => 'low',
            'fastening' => 'laces',
            'country' => 'vietnam',
            'has_bonus' => 1,
            'promo_2for1' => 0,
            'is_exclusive' => 0,
            'rating' => 4.8,
            'reviews_count' => 456,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        // Nike Air Jordan 1
        $this->insert('{{%product}}', [
            'category_id' => 1,
            'brand_id' => 1,
            'name' => 'Nike Air Jordan 1',
            'slug' => 'nike-air-jordan-1',
            'description' => 'Легендарные баскетбольные кроссовки Air Jordan 1. Культовая модель с богатой историей.',
            'price' => 299.00,
            'old_price' => 349.00,
            'main_image' => '/img/products/air-jordan-1.jpg',
            'is_active' => 1,
            'is_featured' => 1,
            'stock_status' => 'in_stock',
            'material' => 'leather',
            'season' => 'all',
            'gender' => 'unisex',
            'height' => 'high',
            'fastening' => 'laces',
            'country' => 'china',
            'has_bonus' => 0,
            'promo_2for1' => 0,
            'is_exclusive' => 1,
            'rating' => 5.0,
            'reviews_count' => 2134,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        // Adidas Stan Smith
        $this->insert('{{%product}}', [
            'category_id' => 1,
            'brand_id' => 2,
            'name' => 'Adidas Stan Smith',
            'slug' => 'adidas-stan-smith',
            'description' => 'Знаменитые теннисные кроссовки Adidas Stan Smith. Минимализм и элегантность в каждой детали.',
            'price' => 119.00,
            'old_price' => null,
            'main_image' => '/img/products/adidas-stan-smith.jpg',
            'is_active' => 1,
            'is_featured' => 1,
            'stock_status' => 'in_stock',
            'material' => 'leather',
            'season' => 'all',
            'gender' => 'unisex',
            'height' => 'low',
            'fastening' => 'laces',
            'country' => 'vietnam',
            'has_bonus' => 1,
            'promo_2for1' => 0,
            'is_exclusive' => 0,
            'rating' => 4.7,
            'reviews_count' => 892,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        
        echo "✓ Добавлено 10 тестовых товаров с новыми полями\n";
    }

    public function safeDown()
    {
        $this->delete('{{%product}}', ['slug' => [
            'nike-air-max-90',
            'adidas-ultraboost-21',
            'new-balance-574',
            'puma-rs-x',
            'converse-chuck-taylor-all-star',
            'vans-old-skool',
            'reebok-classic-leather',
            'asics-gel-kayano-28',
            'nike-air-jordan-1',
            'adidas-stan-smith',
        ]]);
    }
}
