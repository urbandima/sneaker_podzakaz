<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\services\Catalog\FilterBuilder;

/**
 * Unit тесты для FilterBuilder
 */
class FilterBuilderTest extends TestCase
{
    /**
     * Тест форматирования активных фильтров
     */
    public function testFormatActiveFiltersEmpty(): void
    {
        $result = FilterBuilder::formatActiveFilters([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Тест форматирования фильтра цены
     */
    public function testFormatActiveFiltersWithPrice(): void
    {
        $filters = [
            'price_from' => 100,
            'price_to' => 500,
        ];
        
        $result = FilterBuilder::formatActiveFilters($filters);
        
        $this->assertNotEmpty($result);
        $this->assertEquals('price', $result[0]['type']);
        $this->assertStringContainsString('100', $result[0]['label']);
        $this->assertStringContainsString('500', $result[0]['label']);
    }

    /**
     * Тест форматирования фильтра размеров
     */
    public function testFormatActiveFiltersWithSizes(): void
    {
        $filters = [
            'sizes' => ['40', '41', '42'],
            'size_system' => 'eu',
        ];
        
        $result = FilterBuilder::formatActiveFilters($filters);
        
        $this->assertCount(3, $result);
        foreach ($result as $item) {
            $this->assertEquals('size', $item['type']);
            $this->assertStringContainsString('EU', $item['label']);
        }
    }

    /**
     * Тест получения лейблов полей продукта
     */
    public function testGetProductFieldLabels(): void
    {
        // Используем рефлексию для доступа к protected методу
        $reflection = new \ReflectionClass(FilterBuilder::class);
        $method = $reflection->getMethod('getProductFieldLabels');
        $method->setAccessible(true);
        
        $genderLabels = $method->invoke(null, 'gender');
        $this->assertArrayHasKey('male', $genderLabels);
        $this->assertArrayHasKey('female', $genderLabels);
        $this->assertArrayHasKey('unisex', $genderLabels);
        
        $seasonLabels = $method->invoke(null, 'season');
        $this->assertArrayHasKey('summer', $seasonLabels);
        $this->assertArrayHasKey('winter', $seasonLabels);
    }

    /**
     * Тест статических фильтров
     */
    public function testBuildDiscountFilter(): void
    {
        $reflection = new \ReflectionClass(FilterBuilder::class);
        $method = $reflection->getMethod('buildDiscountFilter');
        $method->setAccessible(true);
        
        $result = $method->invoke(null);
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('value', $result[0]);
        $this->assertArrayHasKey('label', $result[0]);
    }

    /**
     * Тест построения фильтра рейтинга
     */
    public function testBuildRatingFilter(): void
    {
        $reflection = new \ReflectionClass(FilterBuilder::class);
        $method = $reflection->getMethod('buildRatingFilter');
        $method->setAccessible(true);
        
        $result = $method->invoke(null);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result); // 4★ и 3★
    }

    /**
     * Тест построения фильтра условий
     */
    public function testBuildConditionsFilter(): void
    {
        $reflection = new \ReflectionClass(FilterBuilder::class);
        $method = $reflection->getMethod('buildConditionsFilter');
        $method->setAccessible(true);
        
        $result = $method->invoke(null);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result); // new, hit, in_stock
    }
}
