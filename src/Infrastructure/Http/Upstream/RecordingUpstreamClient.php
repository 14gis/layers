<?php
namespace Gis14\Layers\Infrastructure\Http\Upstream;

final class RecordingUpstreamClient implements UpstreamClientInterface
{
    public function __construct(
        private UpstreamClientInterface $inner,
        private string $dir,
        private ?SimpleJsonLogger $logger = null
    ) {}

    public function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $t0 = hrtime(true);
        $res = $this->inner->request($method, $url, $headers, $body);
        $dt = (hrtime(true)-$t0)/1e6;
        
        $key = $this->cassetteKey($method, $url, $body);
        if (!is_dir($this->dir)) @mkdir($this->dir, 0777, true);

        $body = $res['body'] ?? '';
        $prettyBody = $body;

        // try to decode as JSON for pretty printing
        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $prettyBody = $decoded; // echtes Array für json_encode unten
        }
        
        $cass = [
            'req' => [
                'method'  => $method,
                'url'     => $url,
                'headers' => $this->sanitize($headers),
                'body'    => $body,
            ],
            'res' => [
                'status'  => $res['status'],
                'headers' => $this->sanitize($res['headers']),
                'body'    => $prettyBody, // Array statt escaped String
                'originalBody'    => $body, // Array statt escaped String
            ],
            'meta' => [
                'duration_ms' => $dt,
            ],
        ];
        
        
        file_put_contents(
            $this->dir . '/' . $key . '.json',
            json_encode($cass, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->logger?->log('info', ['event'=>'record','url'=>$url,'status'=>$res['status'],'ms'=>$dt]);
        return $res;
    }

    private function cassetteKey(string $method, string $url, ?string $body): string
    {
        return hash('sha256', $method.'|'.$url.'|'.($body ?? ''));
    }

    private function sanitize(array $headers): array
    {
        $blocked = ['authorization','x-api-key','x-api-key','cookie'];
        $out = [];
        foreach ($headers as $k=>$v) {
            $out[$k] = in_array(strtolower($k), $blocked, true) ? '***' : $v;
        }
        return $out;
    }
}
