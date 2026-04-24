<?php

namespace app\backend\shared\helpers;

/**
 * TextHelper — общие текстовые утилиты для отображения данных.
 */
class TextHelper
{
    /**
     * Склоняет слово по числу (русский язык).
     *
     * Пример: pluralizeRu(1, ['товар','товара','товаров']) → 'товар'
     *          pluralizeRu(2, ['товар','товара','товаров']) → 'товара'
     *          pluralizeRu(5, ['товар','товара','товаров']) → 'товаров'
     *
     * @param int   $n      Число
     * @param array $forms  [форма для 1, форма для 2-4, форма для 5+]
     * @return string
     */
    public static function pluralizeRu(int $n, array $forms): string
    {
        $n = abs($n) % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20) {
            return $forms[2];
        }
        if ($n1 > 1 && $n1 < 5) {
            return $forms[1];
        }
        if ($n1 === 1) {
            return $forms[0];
        }
        return $forms[2];
    }

    /**
     * Форматирует количество товаров с правильным падежом.
     *
     * @param int $count
     * @return string  Например: "1 товар", "3 товара", "5 товаров"
     */
    public static function formatProductCount(int $count): string
    {
        return $count . ' ' . self::pluralizeRu($count, ['товар', 'товара', 'товаров']);
    }

    /**
     * Форматирует количество отзывов с правильным падежом.
     *
     * @param int $count
     * @return string  Например: "1 отзыв", "3 отзыва", "5 отзывов"
     */
    public static function formatReviewCount(int $count): string
    {
        return $count . ' ' . self::pluralizeRu($count, ['отзыв', 'отзыва', 'отзывов']);
    }
}
