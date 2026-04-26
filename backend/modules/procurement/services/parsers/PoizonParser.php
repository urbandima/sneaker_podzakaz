<?php

namespace app\backend\modules\procurement\services\parsers;

use Yii;
use app\backend\modules\procurement\models\Buyout;

/**
 * Parses Poizon/Dewu product pages via PoizonApiService or scraping.
 * Falls back to a best-effort scrape if API key is not configured.
 */
class PoizonParser implements BuyoutParserInterface
{
    public function getSourceKey(): string
    {
        return Buyout::SOURCE_POIZON;
    }

    public function supports(string $url): bool
    {
        return (bool)preg_match('#(poizon\.com|dewu\.com|poizon-parser)#i', $url);
    }

    public function parse(string $url): ?array
    {
        // Try API service if available
        if (Yii::$app->has('poizonApi')) {
            try {
                /** @var \app\backend\shared\components\PoizonApiService $api */
                $api = Yii::$app->poizonApi;
                $data = $api->getProductByUrl($url);
                if ($data) {
                    return $this->normalise($data, $url);
                }
            } catch (\Throwable $e) {
                Yii::warning('PoizonParser API error: ' . $e->getMessage(), 'buyout.parser');
            }
        }

        // Fallback: extract spuId from URL
        preg_match('/spuId[=\/](\d+)/i', $url, $m);
        $spuId = $m[1] ?? null;

        return [
            'name'        => $spuId ? 'Poizon SPU #' . $spuId : 'Poizon товар',
            'brand'       => null,
            'image'       => null,
            'images'      => [],
            'price'       => null,
            'currency'    => 'CNY',
            'external_id' => $spuId,
            'size'        => null,
            'source'      => $this->getSourceKey(),
            'raw'         => ['url' => $url],
        ];
    }

    private function normalise(array $data, string $url): array
    {
        return [
            'name'        => $data['title'] ?? $data['name'] ?? 'Poizon товар',
            'brand'       => $data['brand'] ?? null,
            'image'       => $data['image'] ?? ($data['images'][0] ?? null),
            'images'      => $data['images'] ?? [],
            'price'       => isset($data['price']) ? (float)$data['price'] : null,
            'currency'    => 'CNY',
            'external_id' => $data['spuId'] ?? $data['id'] ?? null,
            'size'        => $data['size'] ?? null,
            'source'      => $this->getSourceKey(),
            'raw'         => $data,
        ];
    }
}
