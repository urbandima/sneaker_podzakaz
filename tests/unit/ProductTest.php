<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\modules\catalog\models\Product;
use app\modules\catalog\models\Brand;
use app\modules\catalog\models\Category;

/**
 * Unit тесты для модели Product
 */
class ProductTest extends TestCase
{
    /**
     * @var Product
     */
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->product = new Product();
        $this->product->id = 1;
        $this->product->name = 'Nike Air Max 90';
        $this->product->slug = 'nike-air-max-90';
        $this->product->price = 350.00;
        $this->product->old_price = 450.00;
        $this->product->stock_status = Product::STOCK_IN_STOCK;
        $this->product->is_active = 1;
        $this->product->brand_name = 'Nike';
    }

    /**
     * Тест расчета процента скидки
     */
    public function testGetDiscountPercent(): void
    {
        // Со скидкой
        $this->assertEquals(22, $this->product->getDiscountPercent());
        
        // Без скидки
        $this->product->old_price = null;
        $this->assertEquals(0, $this->product->getDiscountPercent());
        
        // Старая цена меньше новой
        $this->product->old_price = 300.00;
        $this->assertEquals(0, $this->product->getDiscountPercent());
    }

    /**
     * Тест проверки наличия скидки
     */
    public function testHasDiscount(): void
    {
        $this->assertTrue($this->product->hasDiscount());
        
        $this->product->old_price = null;
        $this->assertFalse($this->product->hasDiscount());
    }

    /**
     * Тест проверки статуса наличия
     */
    public function testIsInStock(): void
    {
        $this->assertTrue($this->product->isInStock());
        
        $this->product->stock_status = Product::STOCK_OUT_OF_STOCK;
        $this->assertFalse($this->product->isInStock());
        
        $this->product->stock_status = Product::STOCK_PREORDER;
        $this->assertFalse($this->product->isInStock());
    }

    /**
     * Тест получения лейбла статуса
     */
    public function testGetStockStatusLabel(): void
    {
        $this->assertEquals('В наличии', $this->product->getStockStatusLabel());
        
        $this->product->stock_status = Product::STOCK_OUT_OF_STOCK;
        $this->assertEquals('Нет в наличии', $this->product->getStockStatusLabel());
        
        $this->product->stock_status = Product::STOCK_PREORDER;
        $this->assertEquals('Под заказ', $this->product->getStockStatusLabel());
    }

    /**
     * Тест генерации URL
     */
    public function testGetUrl(): void
    {
        $url = $this->product->getUrl();
        $this->assertStringContainsString('nike-air-max-90', $url);
    }

    /**
     * Тест валидации обязательных полей
     */
    public function testValidationRules(): void
    {
        $product = new Product();
        $product->name = '';
        
        $this->assertFalse($product->validate(['name']));
        $this->assertTrue(isset($product->errors['name']));
    }

    /**
     * Тест константы статусов
     */
    public function testStockStatusConstants(): void
    {
        $this->assertEquals('in_stock', Product::STOCK_IN_STOCK);
        $this->assertEquals('out_of_stock', Product::STOCK_OUT_OF_STOCK);
        $this->assertEquals('preorder', Product::STOCK_PREORDER);
    }

    /**
     * Тест получения SEO заголовка
     */
    public function testGetMetaTitle(): void
    {
        // Без кастомного мета-заголовка
        $title = $this->product->getMetaTitle();
        $this->assertStringContainsString('Nike Air Max 90', $title);
        
        // С кастомным мета-заголовком
        $this->product->meta_title = 'Custom SEO Title';
        $this->assertEquals('Custom SEO Title', $this->product->getMetaTitle());
    }
}
