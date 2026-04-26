<?php

namespace app\backend\modules\procurement\services\parsers;

interface BuyoutParserInterface
{
    /**
     * @param string $url
     * @return array{
     *   name: string,
     *   brand: string|null,
     *   image: string|null,
     *   images: string[],
     *   price: float|null,
     *   currency: string,
     *   external_id: string|null,
     *   size: string|null,
     *   source: string,
     *   raw: array
     * }|null  null if parsing failed
     */
    public function parse(string $url): ?array;

    public function supports(string $url): bool;

    public function getSourceKey(): string;
}
