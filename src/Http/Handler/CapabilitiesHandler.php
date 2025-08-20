<?php // src/Http/Handler/CapabilitiesHandler.php
namespace Gis14\Layers\Http\Handler;

use Gis14\Layers\Infrastructure\Repository\LayerRepositoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class CapabilitiesHandler implements RequestHandlerInterface
{
    public function __construct(
        private LayerRepositoryInterface $repo,
        private ResponseFactoryInterface $responses
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = $request->getQueryParams()['context'] ?? 'geochron';
        $layers = $this->repo->findByContext($context);
        $roles = array_values(array_unique(array_filter(array_map(fn($l) => $l['role'] ?? null, $layers))));
        $payload = ['context' => $context, 'roles' => $roles, 'layers' => $layers];
        $res = $this->responses->createResponse(200)->withHeader('Content-Type','application/json');
        $res->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        return $res;
    }
}
