<?php

namespace app\backend\modules\procurement\services\parsers;

use app\backend\modules\procurement\models\Buyout;

/** Fallback parser: extracts basic Open Graph / meta data via HTTP. */
class GenericParser implements BuyoutParserInterface
{
    public function getSourceKey(): string
    {
        return Buyout::SOURCE_MANUAL;
    }

    public function supports(string $url): bool
    {
        return (bool)filter_var($url, FILTER_VALIDATE_URL);
    }

    public function parse(string $url): ?array
    {
        $name  = null;
        $image = null;
        $price = null;

        try {
            $ctx  = stream_context_create(['http' => ['timeout' => 8, 'header' => "User-Agent: Mozilla/5.0\r\n"]]);
            $html = @file_get_contents($url, false, $ctx);

            if ($html) {
                if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\'](.*?)["\']/i', $html, $m)) {
                    $name = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
                } elseif (preg_match('/<title>(.*?)<\/title>/i', $html, $m)) {
                    $name = html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5);
                }

                if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\'](.*?)["\']/i', $html, $m)) {
                    $image = $m[1];
                }

                if (preg_match('/["\']price["\']\s*:\s*["\']([\d.,]+)["\']/', $html, $m)) {
                    $price = (float)str_replace(',', '.', $m[1]);
                }
            }
        } catch (\Throwable) {
        }

        $host = parse_url($url, PHP_URL_HOST) ?? 'web';

        return [
            'name'        => $name ?? $host . ' товар',
            'brand'       => null,
            'image'       => $image,
            'images'      => $image ? [$image] : [],
            'price'       => $price,
            'currency'    => 'BYN',
            'external_id' => null,
            'size'        => null,
            'source'      => $this->getSourceKey(),
            'raw'         => ['url' => $url],
        ];
    }
}
