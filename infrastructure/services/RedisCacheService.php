<?php

namespace app\infrastructure\services;

use Yii;
use yii\redis\Connection;

/**
 * RedisCacheService - Сервис для кеширования в Redis
 * 
 * НАЗНАЧЕНИЕ:
 * - Кеширование каталога товаров
 * - Кеширование корзины гостей
 * - Хранение сессий
 * - Управление TTL и инвалидацией кеша
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * $cache = new RedisCacheService();
 * $cache->setCatalogCache('nike', $products, 3600);
 * $products = $cache->getCatalogCache('nike');
 */
class RedisCacheService
{
    /** @var Connection */
    private $redis;
    
    /** @var string Префикс для ключей каталога */
    const CATALOG_PREFIX = 'catalog:';
    
    /** @var string Префикс для ключей корзины */
    const CART_PREFIX = 'cart:guest:';
    
    /** @var string Префикс для ключей сессий */
    const SESSION_PREFIX = 'session:';
    
    /** @var int TTL для кеша каталога (1 час) */
    const CATALOG_TTL = 3600;
    
    /** @var int TTL для корзины гостей (7 дней) */
    const CART_TTL = 604800;
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->redis = Yii::$app->redis;
    }
    
    /**
     * Получить кеш каталога
     * 
     * @param string $key Ключ кеша (например, 'search:nike' или 'category:1')
     * @return array|null
     */
    public function getCatalogCache(string $key): ?array
    {
        try {
            $cacheKey = self::CATALOG_PREFIX . $key;
            $data = $this->redis->get($cacheKey);
            
            if ($data === null || $data === false) {
                return null;
            }
            
            return json_decode($data, true);
            
        } catch (\Exception $e) {
            Yii::error("Ошибка получения кеша каталога: " . $e->getMessage(), 'redis');
            return null;
        }
    }
    
    /**
     * Установить кеш каталога
     * 
     * @param string $key
     * @param array $data
     * @param int|null $ttl TTL в секундах (по умолчанию 1 час)
     * @return bool
     */
    public function setCatalogCache(string $key, array $data, ?int $ttl = null): bool
    {
        try {
            $cacheKey = self::CATALOG_PREFIX . $key;
            $ttl = $ttl ?? self::CATALOG_TTL;
            
            $this->redis->setex($cacheKey, $ttl, json_encode($data));
            
            return true;
            
        } catch (\Exception $e) {
            Yii::error("Ошибка установки кеша каталога: " . $e->getMessage(), 'redis');
            return false;
        }
    }
    
    /**
     * Инвалидировать весь кеш каталога
     * 
     * @return bool
     */
    public function invalidateCatalogCache(): bool
    {
        try {
            $keys = $this->redis->keys(self::CATALOG_PREFIX . '*');
            
            if (!empty($keys)) {
                $this->redis->del(...$keys);
            }
            
            Yii::info("Кеш каталога инвалидирован", 'redis');
            return true;
            
        } catch (\Exception $e) {
            Yii::error("Ошибка инвалидации кеша каталога: " . $e->getMessage(), 'redis');
            return false;
        }
    }
    
    /**
     * Получить корзину гостя
     * 
     * @param string $sessionId ID сессии гостя
     * @return array|null
     */
    public function getGuestCart(string $sessionId): ?array
    {
        try {
            $cacheKey = self::CART_PREFIX . $sessionId;
            $data = $this->redis->get($cacheKey);
            
            if ($data === null || $data === false) {
                return null;
            }
            
            // Продлеваем TTL при каждом обращении
            $this->redis->expire($cacheKey, self::CART_TTL);
            
            return json_decode($data, true);
            
        } catch (\Exception $e) {
            Yii::error("Ошибка получения корзины гостя: " . $e->getMessage(), 'redis');
            return null;
        }
    }
    
    /**
     * Установить корзину гостя
     * 
     * @param string $sessionId
     * @param array $cartData
     * @return bool
     */
    public function setGuestCart(string $sessionId, array $cartData): bool
    {
        try {
            $cacheKey = self::CART_PREFIX . $sessionId;
            
            $this->redis->setex($cacheKey, self::CART_TTL, json_encode($cartData));
            
            return true;
            
        } catch (\Exception $e) {
            Yii::error("Ошибка установки корзины гостя: " . $e->getMessage(), 'redis');
            return false;
        }
    }
    
    /**
     * Добавить товар в корзину гостя
     * 
     * @param string $sessionId
     * @param array $item ['product_id' => int, 'quantity' => int, 'size' => string, 'price' => float]
     * @return bool
     */
    public function addToGuestCart(string $sessionId, array $item): bool
    {
        try {
            $cart = $this->getGuestCart($sessionId) ?? ['items' => [], 'total' => 0];
            
            // Проверяем, есть ли уже такой товар
            $found = false;
            foreach ($cart['items'] as &$cartItem) {
                if ($cartItem['product_id'] == $item['product_id'] && 
                    ($cartItem['size'] ?? null) == ($item['size'] ?? null)) {
                    $cartItem['quantity'] += $item['quantity'];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $cart['items'][] = $item;
            }
            
            // Пересчитываем итого
            $cart['total'] = 0;
            foreach ($cart['items'] as $cartItem) {
                $cart['total'] += $cartItem['price'] * $cartItem['quantity'];
            }
            
            return $this->setGuestCart($sessionId, $cart);
            
        } catch (\Exception $e) {
            Yii::error("Ошибка добавления в корзину гостя: " . $e->getMessage(), 'redis');
            return false;
        }
    }
    
    /**
     * Удалить товар из корзины гостя
     * 
     * @param string $sessionId
     * @param int $productId
     * @param string|null $size
     * @return bool
     */
    public function removeFromGuestCart(string $sessionId, int $productId, ?string $size = null): bool
    {
        try {
            $cart = $this->getGuestCart($sessionId);
            
            if (!$cart) {
                return true;
            }
            
            $cart['items'] = array_filter($cart['items'], function($item) use ($productId, $size) {
                return !($item['product_id'] == $productId && ($item['size'] ?? null) == $size);
            });
            
            // Пересчитываем итого
            $cart['total'] = 0;
            foreach ($cart['items'] as $cartItem) {
                $cart['total'] += $cartItem['price'] * $cartItem['quantity'];
            }
            
            return $this->setGuestCart($sessionId, $cart);
            
        } catch (\Exception $e) {
            Yii::error("Ошибка удаления из корзины гостя: " . $e->getMessage(), 'redis');
            return false;
        }
    }
    
    /**
     * Очистить корзину гостя
     * 
     * @param string $sessionId
     * @return bool
     */
    public function clearGuestCart(string $sessionId): bool
    {
        try {
            $cacheKey = self::CART_PREFIX . $sessionId;
            $this->redis->del($cacheKey);
            
            return true;
            
        } catch (\Exception $e) {
            Yii::error("Ошибка очистки корзины гостя: " . $e->getMessage(), 'redis');
            return false;
        }
    }
    
    /**
     * Получить статистику кеша
     * 
     * @return array
     */
    public function getCacheStats(): array
    {
        try {
            $catalogKeys = count($this->redis->keys(self::CATALOG_PREFIX . '*'));
            $cartKeys = count($this->redis->keys(self::CART_PREFIX . '*'));
            $sessionKeys = count($this->redis->keys(self::SESSION_PREFIX . '*'));
            
            $info = $this->redis->info('memory');
            
            return [
                'catalog_keys' => $catalogKeys,
                'cart_keys' => $cartKeys,
                'session_keys' => $sessionKeys,
                'total_keys' => $catalogKeys + $cartKeys + $sessionKeys,
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'memory_peak' => $info['used_memory_peak_human'] ?? 'N/A'
            ];
            
        } catch (\Exception $e) {
            Yii::error("Ошибка получения статистики кеша: " . $e->getMessage(), 'redis');
            return [];
        }
    }
    
    /**
     * Очистить весь кеш
     * 
     * @return bool
     */
    public function flushAll(): bool
    {
        try {
            $this->redis->flushdb();
            
            Yii::info("Весь кеш Redis очищен", 'redis');
            return true;
            
        } catch (\Exception $e) {
            Yii::error("Ошибка очистки кеша: " . $e->getMessage(), 'redis');
            return false;
        }
    }
}
