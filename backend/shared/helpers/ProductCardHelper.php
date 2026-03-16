<?php

/**
 * ProductCardHelper — Хелпер для подготовки данных карточки товара
 * 
 * НАЗНАЧЕНИЕ:
 * Подготовка данных для отображения карточки товара:
 * размеры, цены, бейджи, галерея. Оптимизация для фронтенда.
 * 
 * МЕТОДЫ:
 * - normalizeSelectedSizes(): нормализация параметра размеров
 * - resolveSizeField(): определение поля размера по системе
 * - prepareCardData(): подготовка данных карточки
 * - getBadges(): получение бейджей (новинка, скидка)
 * - getGallery(): получение галереи изображений
 * 
 * КОНСТАНТЫ:
 * - LAZY_PLACEHOLDER: placeholder для lazy loading
 * - MAX_SIZE_BADGES: максимум бейджей размеров
 * - MAX_GALLERY_IMAGES: максимум изображений в галерее
 * - NEW_BADGE_PERIOD: период для бейджа "новинка"
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * ```php
 * $cardData = ProductCardHelper::prepareCardData($product);
 * $badges = ProductCardHelper::getBadges($product);
 * ```
 */
namespace app\backend\shared\helpers;

use app\backend\modules\catalog\models\Product;

/**
 * Вспомогательные методы для подготовки данных карточки товара.
 */
class ProductCardHelper
{
    public const LAZY_PLACEHOLDER = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="10" height="10"%3E%3Crect width="10" height="10" fill="%23f3f4f6"/%3E%3C/svg%3E';
    public const MAX_SIZE_BADGES = 6;
    public const MAX_GALLERY_IMAGES = 4;
    public const NEW_BADGE_PERIOD = 604800; // 7 дней
    public const CRITICAL_CARD_THRESHOLD = 4;
    public const DEFAULT_SIZE_SYSTEM = 'eu';
    public const PRICE_CURRENCY = 'BYN';

    /**
     * Преобразует параметр запроса размеров в массив.
     *
     * @param array|string|null $selectedSizesParam
     */
    public static function normalizeSelectedSizes(array|string|null $selectedSizesParam): array
    {
        if (empty($selectedSizesParam)) {
            return [];
        }

        return is_array($selectedSizesParam)
            ? $selectedSizesParam
            : array_filter(array_map('trim', explode(',', $selectedSizesParam)));
    }

    public static function resolveSizeField(string $sizeSystem): string
    {
        return match ($sizeSystem) {
            'us' => 'us_size',
            'uk' => 'uk_size',
            'cm' => 'cm_size',
            default => 'eu_size',
        };
    }

    /**
     * Возвращает массив URL-ов изображений для карточки.
     *
     * @return string[]
     */
    public static function buildGalleryImages(Product $product, string $lazyPlaceholder = self::LAZY_PLACEHOLDER): array
    {
        $galleryImages = [];
        $mainImageUrl = $product->getMainImageUrl();

        if ($mainImageUrl) {
            $galleryImages[] = $mainImageUrl;
        }

        if (!empty($product->images)) {
            foreach ($product->images as $image) {
                if (count($galleryImages) >= self::MAX_GALLERY_IMAGES) {
                    break;
                }

                $url = $image->getUrl();
                if ($url && !in_array($url, $galleryImages, true)) {
                    $galleryImages[] = $url;
                }
            }
        }

        if (empty($galleryImages)) {
            $galleryImages[] = $lazyPlaceholder;
        }

        return $galleryImages;
    }

    /**
     * Подготавливает данные для бейджей размеров.
     *
     * @return array{badges: array<int, array{value: string, selected: bool}>, remaining: int}
     */
    public static function prepareSizeBadges(Product $product, string $sizeField, array $selectedSizes): array
    {
        if (empty($product->sizes) || !is_array($product->sizes)) {
            return [
                'badges' => [],
                'remaining' => 0,
            ];
        }

        $availableSizes = array_values(array_filter(
            $product->sizes,
            static fn($size) => $size
                && isset($size->is_available, $size->$sizeField)
                && $size->is_available
                && !empty($size->$sizeField)
        ));

        if (!empty($selectedSizes)) {
            usort(
                $availableSizes,
                static function ($a, $b) use ($selectedSizes, $sizeField) {
                    $aSelected = in_array($a->$sizeField, $selectedSizes, true);
                    $bSelected = in_array($b->$sizeField, $selectedSizes, true);
                    return $aSelected === $bSelected ? 0 : ($aSelected ? -1 : 1);
                }
            );
        }

        $badges = [];
        foreach ($availableSizes as $size) {
            if (count($badges) >= self::MAX_SIZE_BADGES) {
                break;
            }

            $displaySize = (string)$size->$sizeField;
            if ($displaySize === '') {
                continue;
            }

            $badges[] = [
                'value' => $displaySize,
                'selected' => in_array($displaySize, $selectedSizes, true),
            ];
        }

        return [
            'badges' => $badges,
            'remaining' => max(count($availableSizes) - count($badges), 0),
        ];
    }

    /**
     * Строит модель отображения цен.
     *
     * @return array{
     *     showRange: bool,
     *     minPrice: float|null,
     *     maxPrice: float|null,
     *     currentPrice: float|null,
     *     showOldPrice: bool,
     *     oldPrice: float|null,
     *     discountPercent: int|null
     * }
     */
    public static function calculatePriceView(
        Product $product,
        array|string|null $selectedSizesParam,
        array $selectedSizes,
        string $sizeField
    ): array {
        $priceView = [
            'showRange' => false,
            'minPrice' => null,
            'maxPrice' => null,
            'currentPrice' => null,
            'showOldPrice' => false,
            'oldPrice' => null,
            'discountPercent' => null,
        ];

        $hasSelectedSizes = !empty($selectedSizesParam) && !empty($product->sizes);
        if ($hasSelectedSizes) {
            $filteredSizes = array_filter(
                $product->sizes,
                static fn($size) => $size
                    && $size->is_available
                    && in_array($size->$sizeField, $selectedSizes, true)
                    && $size->price_byn > 0
            );

            if (!empty($filteredSizes)) {
                $prices = array_map(static fn($size) => (float)$size->price_byn, $filteredSizes);
                $minPrice = min($prices);
                $maxPrice = max($prices);

                if ($minPrice === $maxPrice) {
                    $priceView['currentPrice'] = $minPrice;
                } else {
                    $priceView['showRange'] = true;
                    $priceView['minPrice'] = $minPrice;
                    $priceView['maxPrice'] = $maxPrice;
                }

                return $priceView;
            }

            $priceView['currentPrice'] = (float)$product->price;
            return $priceView;
        }

        $priceRange = $product->getPriceRange();
        if ($priceRange && $product->hasPriceRange()) {
            $priceView['showRange'] = true;
            $priceView['minPrice'] = (float)$priceRange['min'];
            $priceView['maxPrice'] = (float)$priceRange['max'];
            return $priceView;
        }

        $priceView['currentPrice'] = (float)$product->price;

        if ($product->hasDiscount()) {
            $priceView['showOldPrice'] = true;
            $priceView['oldPrice'] = (float)$product->old_price;
            $priceView['discountPercent'] = (int)$product->getDiscountPercent();
        }

        return $priceView;
    }

    public static function isNewProduct(int|string|null $createdAt): bool
    {
        if (!$createdAt) {
            return false;
        }

        $timestamp = is_int($createdAt)
            ? $createdAt
            : (is_numeric($createdAt) ? (int)$createdAt : strtotime((string)$createdAt));

        if (!$timestamp) {
            return false;
        }

        return $timestamp > (time() - self::NEW_BADGE_PERIOD);
    }
}
