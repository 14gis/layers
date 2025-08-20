<?php // src/Kernel/Dispatcher.php
namespace Gis14\Layers\Kernel;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Dispatcher implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private array $stack;
    private RequestHandlerInterface $fallback;

    public function __construct(array $stack, RequestHandlerInterface $fallback)
    { $this->stack = array_values($stack); $this->fallback = $fallback; }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = array_shift($this->stack);
        if (!$middleware) return $this->fallback->handle($request);
        $next = new self($this->stack, $this->fallback);
        return $middleware->process($request, $next);
    }
}