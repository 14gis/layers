<?php // public/index.php
use FastRoute\RouteCollector;
use Gis14\Layers\Http\Handler\{CapabilitiesHandler, IdentifyHandler, ProxyHandler};
use Gis14\Layers\Http\Middleware\{ErrorMiddleware, RoutingMiddleware};
use Gis14\Layers\Infrastructure\Factory\LayerFactory;
use Gis14\Layers\Infrastructure\Gateway\FakeIdentifyGateway;
use Gis14\Layers\Infrastructure\Gateway\IdentifyGatewayInterface;
use Gis14\Layers\Infrastructure\Gateway\JsonLayerGateway;
use Gis14\Layers\Infrastructure\Http\Upstream\FakeUpstreamClient;
use Gis14\Layers\Infrastructure\Http\Upstream\RealUpstreamClient;
use Gis14\Layers\Infrastructure\Http\Upstream\RecordingUpstreamClient;
use Gis14\Layers\Infrastructure\Http\Upstream\ReplayUpstreamClient;
use Gis14\Layers\Infrastructure\Http\Upstream\SimpleJsonLogger;
use Gis14\Layers\Infrastructure\Http\Upstream\UpstreamClientInterface;
use Gis14\Layers\Infrastructure\Repository\LayerRepository;
use Gis14\Layers\Infrastructure\Repository\LayerRepositoryInterface;
use Gis14\Layers\Kernel\{Dispatcher, TinyContainer};
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

require __DIR__.'/../vendor/autoload.php';

$psr17 = new Psr17Factory();
$creator = new ServerRequestCreator($psr17, $psr17, $psr17, $psr17);
$request = $creator->fromGlobals();

$compiledDir = getenv('COMPILED_DIR') ?: __DIR__ . '/../compiled/schema';

$recordDir = getenv('UPSTREAM_RECORD_DIR') ?: __DIR__ . '/../var/cassettes';
$debugLog  = getenv('LAYERS_DEBUG_LOG') ?: null; // z.B. __DIR__.'/../var/layers-debug.log'
$logger    = new SimpleJsonLogger($debugLog);

$container = new TinyContainer();
$container->set(LayerRepositoryInterface::class, fn() =>
    new LayerRepository(
        new JsonLayerGateway($compiledDir),
        new LayerFactory()
    )
);

$container->set(UpstreamClientInterface::class, function () use ($logger, $recordDir) {
    $mode = getenv('UPSTREAM_MODE') ?: 'real'; // real|fake|record|replay
    return match ($mode) {
        'fake'   => new FakeUpstreamClient(),
        'record' => new RecordingUpstreamClient(new RealUpstreamClient(), $recordDir, $logger),
        'replay' => new ReplayUpstreamClient($recordDir, $logger),
        default  => new RealUpstreamClient(),
    };
});

// choose identify gateway via ENV (default: fake for now)
$container->set(IdentifyGatewayInterface::class, fn() =>
    new FakeIdentifyGateway()
);

$container->set(CapabilitiesHandler::class, fn($c) => new CapabilitiesHandler($c->get(LayerRepositoryInterface::class), $psr17));

$container->set(IdentifyHandler::class, fn($c) =>
    new IdentifyHandler(
        $c->get(LayerRepositoryInterface::class),
        $c->get(IdentifyGatewayInterface::class),
        $psr17
    )
);

$container->set(ProxyHandler::class, fn($c) =>
    new ProxyHandler(
        $c->get(LayerRepositoryInterface::class),
        $psr17,
        $c->get(UpstreamClientInterface::class),
        $logger
    )
);

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/v1/api/capabilities', CapabilitiesHandler::class);
    $r->addRoute('GET', '/v1/api/identify', IdentifyHandler::class);
    // Proxy pattern: /v1/proxy/{context}/{layerId}/{rest}
    $r->addRoute(['GET','POST'], '/v1/proxy/{context}/{layerId}/{rest:.+}', ProxyHandler::class);
});

$middlewareStack = [
    new ErrorMiddleware($psr17),
    new RoutingMiddleware($dispatcher, $container, $psr17),
];
$kernel = new Dispatcher($middlewareStack, new class($psr17) implements Psr\Http\Server\RequestHandlerInterface {
    public function __construct(private $responses) {}
    public function handle(Psr\Http\Message\ServerRequestInterface $request): Psr\Http\Message\ResponseInterface {
        $res = $this->responses->createResponse(404)->withHeader('Content-Type','application/json');
        $res->getBody()->write(json_encode(['error' => 'No route matched']));
        return $res;
    }
});

$response = $kernel->handle($request);

http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) header($name.': '.$value, false);
}
echo (string) $response->getBody();