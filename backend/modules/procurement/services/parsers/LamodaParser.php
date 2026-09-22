<?php

namespace app\backend\modules\procurement\services\parsers;

use app\backend\modules\procurement\models\Buyout;

/**
 * Lamoda URL detection only — automatic card parsing is not implemented (CMP-419).
 * BuyoutController::actionParseUrl() reports this to the operator as a fill-in-manually
 * result rather than as a generic parse failure; see detectSource() usage there.
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
        return null;
    }
}
