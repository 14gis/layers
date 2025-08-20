<?php // src/Http/Handler/IdentifyHandler.php
namespace Gis14\Layers\Http\Handler;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class IdentifyHandler implements RequestHandlerInterface
{
    public function __construct(private ResponseFactoryInterface $responses) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // MVP stub – returns 501 to make the route visible in tests/clients
        $res = $this->responses->createResponse(501)->withHeader('Content-Type','application/json');
        $res->getBody()->write(json_encode([
            'error' => 'Not implemented',
            'hint' => 'Wire ArcGIS identify in next step or use proxy endpoint directly.'
        ]));
        return $res;
    }
}