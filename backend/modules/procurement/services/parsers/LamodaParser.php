<?php

namespace app\backend\modules\procurement\services\parsers;

use Yii;
use app\backend\modules\procurement\models\Buyout;

/**
 * Delegates to existing LamodaParser import service.
 */
class LamodaParser implements BuyoutParserInterface
{
    public function getSourceKey(): string
    {
        return Buyout::SOURCE_LAMODA;
    }

    public function supports(string $url): bool
    {
        return (bool)preg_match('#lamoda\.(by|ru|kz|ua)#i', $url);
    }

    public function parse(string $url): ?array
    {
        try {
            $importParser = new \app\backend\modules\admin\services\import\LamodaParser();
            $raw = $importParser->parseProduct($url);
        } catch (\Throwable $e) {
            Yii::warning('LamodaParser error: ' . $e->getMessage(), 'buyout.parser');
            $raw = [];
        }

        if (empty($raw)) {
            return [
                'name'        => 'Lamoda товар',
                'brand'       => null,
                'image'       => null,
                'images'      => [],
                'price'       => null,
                'currency'    => 'BYN',
                'external_id' => null,
                'size'        => null,
                'source'      => $this->getSourceKey(),
                'raw'         => ['url' => $url],
            ];
        }

        return [
            'name'        => $raw['name'] ?? 'Lamoda товар',
            'brand'       => $raw['brand_name'] ?? null,
            'image'       => $raw['images'][0] ?? null,
            'images'      => $raw['images'] ?? [],
            'price'       => isset($raw['price']) ? (float)$raw['price'] : null,
            'currency'    => 'BYN',
            'external_id' => null,
            'size'        => null,
            'source'      => $this->getSourceKey(),
            'raw'         => $raw,
        ];
    }
}
