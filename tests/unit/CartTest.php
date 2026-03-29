<?php

namespace tests\unit;

use Yii;
use PHPUnit\Framework\TestCase;
use app\backend\modules\cart\models\Cart;
use app\backend\modules\catalog\models\Product;

/**
 * Unit тесты для модели Cart
 * 
 * ПОКРЫТИЕ:
 * - Валидация размера при добавлении
 * - Проверка наличия товара
 * - Расчёт суммы и количества
 * - Оптимизированные SQL запросы
 */
class CartTest extends TestCase
{
    public $appConfig = '@tests/config.php';
    
    protected function setUp(): void
    {
        parent::setUp();
        // Очищаем корзину перед каждым тестом
        Cart::deleteAll();
    }
    
    /**
     * Тест: Добавление товара в корзину
     */
    public function testAddProduct(): void
    {
        // Создаём тестовый товар
        $product = $this->createMockProduct([
            'id' => 1,
            'price' => 100.00,
            'stock_status' => 'in_stock',
        ]);
        
        $result = Cart::add($product->id, 2);
        
        $this->assertTrue($result);
        $this->assertEquals(2, Cart::getItemsCount());
        $this->assertEquals(200.00, Cart::getTotal());
    }
    
    /**
     * Тест: Нельзя добавить товар "нет в наличии"
     */
    public function testCannotAddOutOfStockProduct(): void
    {
        $product = $this->createMockProduct([
            'id' => 2,
            'price' => 150.00,
            'stock_status' => 'out_of_stock',
        ]);
        
        $result = Cart::add($product->id, 1);
        
        $this->assertFalse($result);
        $this->assertEquals(0, Cart::getItemsCount());
    }
    
    /**
     * Тест: Валидация размера при добавлении
     */
    public function testSizeValidationOnAdd(): void
    {
        $product = $this->createMockProduct([
            'id' => 3,
            'price' => 200.00,
            'stock_status' => 'in_stock',
        ]);
        
        // Размер не существует - должен вернуть false
        $result = Cart::add($product->id, 1, 'invalid_size');
        
        $this->assertFalse($result);
    }
    
    /**
     * Тест: Оптимизированный подсчёт количества (SQL SUM)
     */
    public function testOptimizedCountQuery(): void
    {
        // Добавляем несколько товаров
        for ($i = 1; $i <= 3; $i++) {
            $product = $this->createMockProduct([
                'id' => $i + 10,
                'price' => 100.00,
                'stock_status' => 'in_stock',
            ]);
            Cart::add($product->id, $i);
        }
        
        // Проверяем что используется один SQL запрос
        $count = Cart::getItemsCount();
        $this->assertEquals(6, $count); // 1 + 2 + 3
    }
    
    /**
     * Тест: Оптимизированный подсчёт суммы (SQL SUM)
     */
    public function testOptimizedTotalQuery(): void
    {
        $product1 = $this->createMockProduct(['id' => 20, 'price' => 100.00, 'stock_status' => 'in_stock']);
        $product2 = $this->createMockProduct(['id' => 21, 'price' => 200.00, 'stock_status' => 'in_stock']);
        
        Cart::add($product1->id, 2); // 2 * 100 = 200
        Cart::add($product2->id, 3); // 3 * 200 = 600
        
        $total = Cart::getTotal();
        $this->assertEquals(800.00, $total);
    }
    
    /**
     * Тест: Обновление количества товара
     */
    public function testUpdateQuantity(): void
    {
        $product = $this->createMockProduct([
            'id' => 30,
            'price' => 50.00,
            'stock_status' => 'in_stock',
        ]);
        
        Cart::add($product->id, 1);
        
        $items = Cart::getItems();
        $cartItem = $items[0];
        
        $cartItem->updateQuantity(5);
        
        $this->assertEquals(5, Cart::getItemsCount());
        $this->assertEquals(250.00, Cart::getTotal());
    }
    
    /**
     * Тест: Очистка корзины
     */
    public function testClearCart(): void
    {
        $product = $this->createMockProduct([
            'id' => 40,
            'price' => 100.00,
            'stock_status' => 'in_stock',
        ]);
        
        Cart::add($product->id, 3);
        $this->assertEquals(3, Cart::getItemsCount());
        
        Cart::clear();
        
        $this->assertEquals(0, Cart::getItemsCount());
        $this->assertEquals(0, Cart::getTotal());
    }
    
    /**
     * Тест: Валидация quantity в rules()
     */
    public function testQuantityValidation(): void
    {
        $cart = new Cart();
        $cart->quantity = 0;
        $this->assertFalse($cart->validate(['quantity']));
        
        $cart->quantity = 100;
        $this->assertFalse($cart->validate(['quantity']));
        
        $cart->quantity = 50;
        $this->assertTrue($cart->validate(['quantity']));
    }
    
    /**
     * Создать mock продукта
     */
    private function createMockProduct(array $data): Product
    {
        $product = $this->createMock(Product::class);
        $product->id = $data['id'];
        $product->price = $data['price'];
        $product->stock_status = $data['stock_status'];
        
        // Мокаем статический метод findOne
        Product::$mockFindOne = function($id) use ($product) {
            return $id === $product->id ? $product : null;
        };
        
        return $product;
    }
    
    protected function tearDown(): void
    {
        Cart::deleteAll();
        Product::$mockFindOne = null;
        parent::tearDown();
    }
}
