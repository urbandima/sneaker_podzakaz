<?php

namespace tests\unit;

use app\backend\shared\components\AmocrmClient;

/**
 * Test double for AmocrmClient (CMP-371, п.1.1).
 *
 * Overrides only the curl transport seam, everything else
 * (retry/refresh/logging) runs exactly as in production AmocrmClient.
 */
class FakeAmocrmClient extends AmocrmClient
{
    /** @var array<int,array{httpCode:int,body:?string}> */
    private array $queue = [];

    /** @var array<int,array{method:string,url:string,payload:?string}> */
    public array $calls = [];

    public function queueResponse(int $httpCode, ?string $body): void
    {
        $this->queue[] = ['httpCode' => $httpCode, 'body' => $body];
    }

    public function setCredentialsForTesting(string $domain, string $accessToken, bool $usingLongToken = true): void
    {
        parent::setCredentialsForTesting($domain, $accessToken, $usingLongToken);
    }

    protected function transport(string $method, string $url, array $headers, ?string $payload): array
    {
        $this->calls[] = ['method' => $method, 'url' => $url, 'payload' => $payload];

        $next = array_shift($this->queue) ?? ['httpCode' => 200, 'body' => null];

        return ['httpCode' => $next['httpCode'], 'body' => $next['body'], 'curlErrno' => 0];
    }
}
