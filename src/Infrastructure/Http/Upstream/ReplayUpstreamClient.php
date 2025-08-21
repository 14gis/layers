<?php
namespace Gis14\Layers\Infrastructure\Http\Upstream;

final class ReplayUpstreamClient implements UpstreamClientInterface
{
    public function __construct(private string $dir, private ?SimpleJsonLogger $logger = null) {}

    public function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $key = hash('sha256', $method.'|'.$url.'|'.($body ?? ''));
        $file = $this->dir.'/'.$key.'.json';
        if (!is_file($file)) {
            $this->logger?->log('warning', ['event'=>'replay-miss','url'=>$url]);
            return ['status'=>404,'headers'=>['Content-Type'=>'application/json'],'body'=>json_encode(['error'=>'Replay miss','url'=>$url])];
        }
        $cass = json_decode(file_get_contents($file), true);
        $this->logger?->log('info', ['event'=>'replay-hit','url'=>$url,'status'=>$cass['res']['status'] ?? 200]);
        return [
            'status'  => (int)($cass['res']['status'] ?? 200),
            'headers' => $cass['res']['headers'] ?? ['Content-Type'=>'application/octet-stream'],
            'body'    => (string)($cass['res']['body'] ?? ''),
        ];
    }
}
