<?php

namespace app\backend\shared\helpers;

use app\backend\modules\catalog\models\Product;

/**
 * SlugHelper — Единственный источник истины для генерации slug-ов.
 *
 * Заменяет дублированные private-методы generateSlug() / slugify()
 * в GeneratorController, ParserController, PoizonImportJsonController.
 */
class SlugHelper
{
    /**
     * Таблица транслитерации кириллицы → латиницы.
     */
    private static array $converter = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];

    /**
     * Транслитерировать и превратить строку в slug.
     *
     * @param string $text   — исходная строка (может содержать кириллицу)
     * @param int    $maxLen — максимальная длина slug
     * @return string
     */
    public static function slugify(string $text, int $maxLen = 100): string
    {
        $slug = mb_strtolower($text);
        $slug = strtr($slug, self::$converter);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        return substr($slug, 0, $maxLen);
    }

    /**
     * Генерировать уникальный slug для Product.
     *
     * @param string $name — название товара
     * @return string
     */
    public static function uniqueProductSlug(string $name): string
    {
        $slug = self::slugify($name);

        $originalSlug = $slug;
        $counter = 1;
        while (Product::findOne(['slug' => $slug])) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Генерировать slug с uniqid (гарантированно уникальный без DB-проверки).
     *
     * @param string $name — название
     * @return string
     */
    public static function randomProductSlug(string $name): string
    {
        return self::slugify($name) . '-' . uniqid();
    }
}
