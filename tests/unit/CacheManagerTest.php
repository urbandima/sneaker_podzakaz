<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\shared\components\CacheManager;

/**
 * Unit тесты для CacheManager
 */
class CacheManagerTest extends TestCase
{
    /**
     * Тест констант префиксов
     */
    public function testPrefixConstants(): void
    {
        $this->assertEquals('filters:', CacheManager::PREFIX_FILTERS);
        $this->assertEquals('products:', CacheManager::PREFIX_PRODUCTS);
        $this->assertEquals('catalog:', CacheManager::PREFIX_CATALOG);
        $this->assertEquals('count:', CacheManager::PREFIX_COUNT);
        $this->assertEquals('search:', CacheManager::PREFIX_SEARCH);
        $this->assertEquals('api:', CacheManager::PREFIX_API);
    }

    /**
     * Тест констант тегов
     */
    public function testTagConstants(): void
    {
        $this->assertEquals('filters', CacheManager::TAG_FILTERS);
        $this->assertEquals('products', CacheManager::TAG_PRODUCTS);
        $this->assertEquals('catalog', CacheManager::TAG_CATALOG);
        $this->assertEquals('brands', CacheManager::TAG_BRANDS);
        $this->assertEquals('categories', CacheManager::TAG_CATEGORIES);
    }

    /**
     * Тест констант TTL
     */
    public function testTtlConstants(): void
    {
        $this->assertEquals(300, CacheManager::TTL_SHORT);      // 5 минут
        $this->assertEquals(1800, CacheManager::TTL_MEDIUM);    // 30 минут
        $this->assertEquals(3600, CacheManager::TTL_LONG);      // 1 час
        $this->assertEquals(86400, CacheManager::TTL_VERY_LONG); // 24 часа
    }
}
