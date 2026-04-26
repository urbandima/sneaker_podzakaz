<?php

namespace app\backend\modules\procurement\services;

use app\backend\modules\procurement\services\parsers\BuyoutParserInterface;
use app\backend\modules\procurement\services\parsers\PoizonParser;
use app\backend\modules\procurement\services\parsers\LamodaParser;
use app\backend\modules\procurement\services\parsers\AliexpressParser;
use app\backend\modules\procurement\services\parsers\GenericParser;

class BuyoutUrlParserService
{
    /** @var BuyoutParserInterface[] */
    private array $parsers;

    public function __construct()
    {
        $this->parsers = [
            new PoizonParser(),
            new LamodaParser(),
            new AliexpressParser(),
            new GenericParser(), // must be last
        ];
    }

    public function parse(string $url): ?array
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($url)) {
                return $parser->parse($url);
            }
        }
        return null;
    }

    public function detectSource(string $url): string
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($url) && $parser->getSourceKey() !== 'manual') {
                return $parser->getSourceKey();
            }
        }
        return 'manual';
    }
}
