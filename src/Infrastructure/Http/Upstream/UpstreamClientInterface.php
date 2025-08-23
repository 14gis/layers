<?php
namespace Gis14\Layers\Infrastructure\Http\Upstream;

interface UpstreamClientInterface
{
    /**
     * @param string $method GET/POST/...
     * @param string $url    Absolute URL inkl. Query
     * @param array<string,string> $headers
     * @param string|null $body
     * @param string|null $context
     * @return array{status:int, headers:array<string,string>, body:string}
     */
    public function request(string $method, string $url, array $headers = [], ?string $body = null, ?string $context = null): array;
}
