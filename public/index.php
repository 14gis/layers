<?php // public/index.php
use FastRoute\RouteCollector;
use Gis14\Layers\Infrastructure\Factory\LayerFactory;
use Gis14\Layers\Infrastructure\Gateway\JsonLayerGateway;
use Gis14\Layers\Infrastructure\Repository\LayerRepository;
use Gis14\Layers\Infrastructure\Repository\LayerRepositoryInterface;
use Gis14\Layers\Kernel\{Dispatcher, TinyContainer};
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

require __DIR__.'/../vendor/autoload.php';

$psr17 = new Psr17Factory();
$creator = new ServerRequestCreator($psr17, $psr17, $psr17, $psr17);
$request = $creator->fromGlobals();

$compiledDir = getenv('COMPILED_DIR') ?: __DIR__.'/../compiled/schema';

$container = new TinyContainer();
$container->set(LayerRepositoryInterface::class, fn() =>
    new LayerRepository(
        new JsonLayerGateway($compiledDir),
        new LayerFactory()
    )
);


$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
});

$middlewareStack = [
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