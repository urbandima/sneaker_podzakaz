<?php

namespace app\backend\modules\procurement\services\parsers;

use app\backend\modules\procurement\models\Buyout;

/** Stub — AliExpress requires dedicated scraping solution. */
class AliexpressParser implements BuyoutParserInterface
{
    public function getSourceKey(): string
    {
        return Buyout::SOURCE_ALIEXPRESS;
    }

    public function supports(string $url): bool
    {
        return (bool)preg_match('#aliexpress\.(com|ru)#i', $url);
    }

    public function parse(string $url): ?array
    {
        // Extract item ID from URL
        preg_match('/item\/(\d+)/i', $url, $m);
        $itemId = $m[1] ?? null;

        return [
            'name'        => $itemId ? 'AliExpress #' . $itemId : 'AliExpress товар',
            'brand'       => null,
            'image'       => null,
            'images'      => [],
            'price'       => null,
            'currency'    => 'CNY',
            'external_id' => $itemId,
            'size'        => null,
            'source'      => $this->getSourceKey(),
            'raw'         => ['url' => $url, '_stub' => true],
        ];
    }
}
