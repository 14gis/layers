<?php
// tests/Integration/IdentifyInProcessTest.php
use Gis14\Layers\Http\Handler\IdentifyHandler;
use Gis14\Layers\Infrastructure\Repository\LayerRepository;
use Gis14\Layers\Infrastructure\Gateway\JsonLayerGateway;
use Gis14\Layers\Infrastructure\Factory\LayerFactory;
use Gis14\Layers\Infrastructure\Gateway\FakeIdentifyGateway;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;


it('returns empty result outside bbox', function () {
    $repo = new LayerRepository(new JsonLayerGateway(COMPILED_DIR), new LayerFactory());
    $idgw = new FakeIdentifyGateway();
    $psr17 = new Psr17Factory();
    $handler = new IdentifyHandler($repo, $idgw, $psr17);

    $req = (new ServerRequest('GET', '/v1/api/identify'))
        ->withQueryParams(['context'=>'geochron','layer'=>'guek250','x'=>100,'y'=>0,'sr'=>'EPSG:4326']);
    $res = $handler->handle($req);
    expect($res->getStatusCode())->toBe(200);
    $data = json_decode((string)$res->getBody(), true);
    expect($data['result']['features'])->toBeArray()->toHaveLength(0);
});

it('returns a fake feature inside bbox', function () {
    $repo = new LayerRepository(new JsonLayerGateway(COMPILED_DIR), new LayerFactory());
    $idgw = new FakeIdentifyGateway();
    $psr17 = new Psr17Factory();
    $handler = new IdentifyHandler($repo, $idgw, $psr17);

    // Inside Germany bbox per your YAML: approx 10E, 50N
    $req = (new ServerRequest('GET', '/v1/api/identify'))
        ->withQueryParams(['context'=>'geochron','layer'=>'guek250','x'=>10.0,'y'=>50.0,'sr'=>'EPSG:4326']);
    $res = $handler->handle($req);
    expect($res->getStatusCode())->toBe(200);
    $data = json_decode((string)$res->getBody(), true);

    expect($data['result']['features'])->toHaveLength(1);
    $props = $data['result']['features'][0]['properties'];
    expect($props['UNIT_NAME'] ?? null)->toBe('fake:UNIT_NAME');
});
