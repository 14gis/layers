<?php
namespace Gis14\Layers\Infrastructure\Http\Upstream;

final class FakeUpstreamClient implements UpstreamClientInterface
{
    /** @var array<array{pattern:string,status:int,headers:array<string,string>,body:string}> */
    private array $rules;

    public function __construct(array $rules = [])
    {
        // Default: minimal ArcGIS-like OK response
        $this->rules = $rules ?: [[
            'pattern' => '~/(identify|query)\b~',
            'status'  => 200,
            'headers' => ['Content-Type'=>'application/json'],
            'body'    => json_encode(['features'=>[]]),
        ]];
    }

    public function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        foreach ($this->rules as $r) {
            if (preg_match($r['pattern'], $url)) {
                return ['status'=>$r['status'],'headers'=>$r['headers'],'body'=>$r['body']];
            }
        }
        return ['status'=>404,'headers'=>['Content-Type'=>'application/json'],'body'=>json_encode(['error'=>'fake-not-found'])];
    }
}
