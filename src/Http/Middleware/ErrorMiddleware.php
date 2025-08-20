<?php // src/Http/Middleware/ErrorMiddleware.php
namespace Gis14\Layers\Http\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(private ResponseFactoryInterface $responses) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try { return $handler->handle($request); }
        catch (\Throwable $e) {
            $res = $this->responses->createResponse(500)->withHeader('Content-Type','application/problem+json');
            $res->getBody()->write(json_encode([
                'type' => 'about:blank', 'title' => 'Internal Server Error', 'status' => 500,
                'detail' => $e->getMessage(),
            ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
            return $res;
        }
    }
}