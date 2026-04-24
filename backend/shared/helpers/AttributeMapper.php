<?php

namespace app\backend\shared\helpers;

/**
 * AttributeMapper — normalises synonym attribute names to a canonical form.
 *
 * When МойСклад imports product attributes it sometimes uses different names
 * for the same concept ("Цвет обуви" vs "Цвет", "Материал внутренний" vs
 * "Внутренний материал"). This helper maps all known synonyms to a single
 * canonical name so deduplication works correctly.
 */
class AttributeMapper
{
    private static array $map = [
        // Colour
        'цвет обуви'           => 'Цвет',
        'цвет товара'          => 'Цвет',
        'основной цвет'        => 'Цвет',
        'цвет'                 => 'Цвет',

        // Inner material
        'материал внутренний'  => 'Внутренний материал',
        'внутренний материал'  => 'Внутренний материал',
        'подкладка'            => 'Внутренний материал',

        // Outer material
        'материал верха'       => 'Материал верха',
        'верхний материал'     => 'Материал верха',
        'материал внешний'     => 'Материал верха',

        // Sole material
        'материал подошвы'     => 'Материал подошвы',
        'подошва'              => 'Материал подошвы',

        // Season
        'сезон'                => 'Сезон',
        'сезонность'           => 'Сезон',

        // Gender
        'пол'                  => 'Пол',
        'гендер'               => 'Пол',

        // Country
        'страна производства'  => 'Страна',
        'страна-изготовитель'  => 'Страна',
        'страна'               => 'Страна',
    ];

    /**
     * Return the canonical attribute name for the given raw name.
     * Returns the original (title-cased) name if no alias is registered.
     */
    public static function canonical(string $name): string
    {
        $lower = mb_strtolower(trim($name));
        return self::$map[$lower] ?? $name;
    }

    /**
     * Deduplicate a flat array of ['name' => ..., 'value' => ...] rows,
     * collapsing synonyms and keeping the first occurrence.
     *
     * @param array<array{name:string, value:mixed}> $rows
     * @return array<array{name:string, value:mixed}>
     */
    public static function deduplicateRows(array $rows): array
    {
        $seen   = [];
        $result = [];
        foreach ($rows as $row) {
            $canon = self::canonical($row['name'] ?? '');
            if (isset($seen[$canon])) {
                continue;
            }
            $seen[$canon] = true;
            $row['name']  = $canon;
            $result[]     = $row;
        }
        return $result;
    }
}
