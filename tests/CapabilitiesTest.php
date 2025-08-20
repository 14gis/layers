<?php
// tests/CapabilitiesTest.php
use Gis14\Layers\Http\Handler\CapabilitiesHandler;
use Gis14\Layers\Infrastructure\Repository\LayerRepository;
use Gis14\Layers\Infrastructure\Factory\LayerFactory;
use Gis14\Layers\Infrastructure\Gateway\JsonLayerGateway;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;

it('returns guek250 in geochron', function () {
    $compiled = __DIR__ . '/../compiled/schema'; // composer build ran before
    $repo = new LayerRepository(new JsonLayerGateway($compiled), new LayerFactory());
    $psr17 = new Psr17Factory();
    $handler = new CapabilitiesHandler($repo, $psr17);

    $req = (new ServerRequest('GET', '/v1/api/capabilities'))
        ->withQueryParams(['context' => 'geochron']);

    $res = $handler->handle($req);
    expect($res->getStatusCode())->toBe(200);

    $data = json_decode((string)$res->getBody(), true);
    expect($data['context'])->toBe('geochron');

    $ids = array_map(fn($l) => $l['id'] ?? null, $data['layers']);
    expect($ids)->toContain('guek250');
});
