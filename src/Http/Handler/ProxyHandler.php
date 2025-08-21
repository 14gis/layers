<?php // src/Http/Handler/ProxyHandler.php
namespace Gis14\Layers\Http\Handler;

use Gis14\Layers\Infrastructure\Http\Upstream\SimpleJsonLogger;
use Gis14\Layers\Infrastructure\Http\Upstream\UpstreamClientInterface;
use Gis14\Layers\Infrastructure\Repository\LayerRepositoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class ProxyHandler implements RequestHandlerInterface
{
    public function __construct(
        private LayerRepositoryInterface $repo,
        private ResponseFactoryInterface $responses,
        private UpstreamClientInterface $upstream,
        private ?SimpleJsonLogger $logger = null
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $p = (array) $request->getAttribute('routeParams', []);
        $context = $p['context'] ?? 'geochron';
        $layerId = $p['layerId'] ?? '';
        $rest   = $p['rest'] ?? '';
        
        $layer  = $this->repo->findOne($context, $layerId);

        $featUrl = $layer['source']['features']['url'] ?? null;
        $tileUrl = $layer['source']['tiles']['url'] ?? null;

        // Heuristik: alles mit query/identify/FeatureServer → features, sonst tiles
        $useFeatures = preg_match('~\b(query|identify)\b~i', $rest) ||
            str_contains($rest, 'FeatureServer');

        $base = $useFeatures ? $featUrl : $tileUrl;

        if (!$layer || !$base) {
            $res = $this->responses->createResponse(404)->withHeader('Content-Type','application/json');
            $res->getBody()->write(json_encode(['error'=>'Unknown layer or source url missing']));
            return $res;
        }

        $base   = rtrim($base, '/');

        $target = $base . '/' . ltrim($rest, '/');

        // ???
        // NOTE: Some ArcGIS services accept `/query` both on the service root 
        // (`.../MapServer/query`) and on a specific layer (`.../MapServer/{layerId}/query`).
        // Our configured `features.url` usually already points to a concrete layer 
        // (e.g. `.../MapServer/0`), so appending the path is enough.
        // This extra branch tries to be tolerant if a request comes in without layerId
        // and rewrites it to include the first configured layer index.
        //
        // If you always work with explicit layerIds, you can remove this branch safely.
        $needsIndex = preg_match('~^(query|identify)\b~i', $rest) === 1;
        $hasIndex   = preg_match('~^\d+/(query|identify)\b~i', $rest) === 1;

        if ($needsIndex && !$hasIndex && isset($layer['source']['features']['layers'][0])) {
            $idx  = $layer['source']['features']['layers'][0]; // z.B. 0
            $rest = $idx . '/' . ltrim($rest, '/');            // -> "0/query"
        }

        $query = $request->getUri()->getQuery();
        if ($query !== '') $target .= '?' . $query;

        // Header bauen (Secrets optional aus ENV)
        $headers = [];
        if ($ref = getenv('LAYERS_REFERER')) $headers['Referer'] = $ref;
        if ($key = getenv('ARCGIS_API_KEY'))  $headers['X-API-Key'] = $key;
        if (in_array($request->getMethod(), ['POST','PUT','PATCH'], true)) {
            if ($ct = $request->getHeaderLine('Content-Type')) $headers['Content-Type'] = $ct;
        }
        
        $bodyIn = (string) $request->getBody();

        $t0 = hrtime(true);
        $resArr = $this->upstream->request($request->getMethod(), $target, $headers, $bodyIn);
        $dt = (hrtime(true)-$t0)/1e6;

        $this->logger?->log('info', [
            'event'=>'proxy',
            'method'=>$request->getMethod(),
            'target'=>$target,
            'status'=>$resArr['status'],
            'ms'=>$dt
        ]);

        $res = $this->responses->createResponse($resArr['status']);
        foreach ($resArr['headers'] as $k=>$v) $res = $res->withHeader($k, $v);
        $res->getBody()->write($resArr['body']);
        return $res;
    }
}
