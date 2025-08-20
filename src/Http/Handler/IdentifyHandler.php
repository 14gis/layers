<?php
// src/Http/Handler/IdentifyHandler.php
namespace Gis14\Layers\Http\Handler;

use Gis14\Layers\Infrastructure\Gateway\IdentifyGatewayInterface;
use Gis14\Layers\Infrastructure\Repository\LayerRepositoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

final class IdentifyHandler implements RequestHandlerInterface
{
    public function __construct(
        private LayerRepositoryInterface $repo,
        private IdentifyGatewayInterface $gateway,
        private Psr17Factory $responses
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $q = $request->getQueryParams();
        $context = (string)($q['context'] ?? 'geochron');
        $layerId = (string)($q['layer']   ?? '');
        $sr      = (string)($q['sr']      ?? 'EPSG:4326');
        $x = isset($q['x']) ? (float)$q['x'] : null;
        $y = isset($q['y']) ? (float)$q['y'] : null;

        if ($layerId === '' || $x === null || $y === null) {
            return $this->json(400, ['error' => 'Missing required params: layer, x, y']);
        }

        $layer = $this->repo->findOne($context, $layerId);
        if (!$layer) {
            return $this->json(404, ['error' => 'Layer not found in context', 'context' => $context, 'layer' => $layerId]);
        }

        $result = $this->gateway->identifyPoint($layer, $x, $y, $sr);
        return $this->json(200, [
            'context' => $context,
            'layer'   => $layerId,
            'sr'      => $sr,
            'result'  => $result,
        ]);
    }

    private function json(int $code, array $payload): ResponseInterface
    {
        $res = $this->responses->createResponse($code)->withHeader('Content-Type','application/json');
        $res->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        return $res;
    }
}
