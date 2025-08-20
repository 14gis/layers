<?php // src/Http/Middleware/RoutingMiddleware.php
namespace Gis14\Layers\Http\Middleware;

use FastRoute\Dispatcher;
use Gis14\Layers\Kernel\TinyContainer;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class RoutingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private TinyContainer $container,
        private ResponseFactoryInterface $responses
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $res = $this->responses->createResponse(404)->withHeader('Content-Type','application/json');
                $res->getBody()->write(json_encode(['error' => 'Not Found']));
                return $res;
            case Dispatcher::METHOD_NOT_ALLOWED:
                return $this->responses->createResponse(405)->withHeader('Allow', implode(',', $routeInfo[1]));
            case Dispatcher::FOUND:
                $handlerId = $routeInfo[1];
                $vars = $routeInfo[2];
                $resolved = $this->container->get($handlerId);
                return $resolved->handle($request->withAttribute('routeParams', $vars));
        }
        return $handler->handle($request);
    }
}