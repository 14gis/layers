<?php // src/Http/Handler/ProxyHandler.php
namespace Gis14\Layers\Http\Handler;

use Gis14\Layers\Infrastructure\Repository\LayerRepositoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class ProxyHandler implements RequestHandlerInterface
{
    public function __construct(
        private LayerRepositoryInterface $repo,
        private ResponseFactoryInterface $responses
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $p = (array) $request->getAttribute('routeParams', []);
        $context = $p['context'] ?? 'geochron';
        $layerId = $p['layerId'] ?? '';
        $rest = $p['rest'] ?? '';
        $layer = $this->repo->findOne($context, $layerId);
        if (!$layer || empty($layer['source']['url'])) {
            $res = $this->responses->createResponse(404)->withHeader('Content-Type','application/json');
            $res->getBody()->write(json_encode(['error'=>'Unknown layer or source url missing']));
            return $res;
        }
        $base = rtrim($layer['source']['url'], '/');
        $target = $base . '/' . ltrim($rest, '/');
        $query = $request->getUri()->getQuery();
        if ($query !== '') $target .= '?' . $query;

        // cURL pass-through (GET/POST), minimal headers. Never log secrets.
        $ch = curl_init($target);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->getMethod());

        $headers = [];
        // Upstream identity
        if ($ref = getenv('LAYERS_REFERER')) { $headers[] = 'Referer: '.$ref; }
        if ($key = getenv('ARCGIS_API_KEY')) { $headers[] = 'X-API-Key: '.$key; }
        // Content-type for POST
        $body = (string)$request->getBody();
        if (in_array($request->getMethod(), ['POST','PUT','PATCH'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            if ($ct = $request->getHeaderLine('Content-Type')) $headers[] = 'Content-Type: '.$ct;
        }
        if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            $res = $this->responses->createResponse(502)->withHeader('Content-Type','application/json');
            $res->getBody()->write(json_encode(['error'=>'Bad Gateway','detail'=>$err]));
            return $res;
        }
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE) ?: 200;
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE) ?: 0;
        $hdr = substr($resp, 0, $headerSize);
        $bodyOut = substr($resp, $headerSize);
        curl_close($ch);

        $res = $this->responses->createResponse($status);
        foreach (explode("\r\n", trim($hdr)) as $line) {
            if (stripos($line, 'HTTP/') === 0 || $line === '') continue;
            [$name, $value] = array_map('trim', explode(':', $line, 2));
            // drop hop-by-hop headers
            if (in_array(strtolower($name), ['transfer-encoding','connection','keep-alive','proxy-authenticate','proxy-authorization','te','trailer','upgrade'], true)) continue;
            $res = $res->withHeader($name, $value);
        }
        if (!$res->hasHeader('Content-Type')) $res = $res->withHeader('Content-Type','application/octet-stream');
        $res->getBody()->write($bodyOut);
        return $res;
    }
}
