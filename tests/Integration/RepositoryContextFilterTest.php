<?php
// tests/Integration/RepositoryContextFilterTest.php
use Gis14\Layers\Infrastructure\Repository\LayerRepository;
use Gis14\Layers\Infrastructure\Factory\LayerFactory;
use Gis14\Layers\Infrastructure\Gateway\JsonLayerGateway;

it('returns only layers for the requested context', function () {
    $tmp = sys_get_temp_dir().'/layers-ctx-'.bin2hex(random_bytes(4));
    @mkdir($tmp.'/layers', 0777, true);

    file_put_contents($tmp.'/layers/a.json', json_encode([
        'id' => 'a',
        'meta' => ['title' => 'A'],
        'project' => ['allowed' => ['geochron']],
        'context' => ['roles' => ['geology/stratigraphy']],
        'providers' => ['features' => ['type'=>'arcgis-rest','url'=>'u','layerId'=>0]],
    ], JSON_UNESCAPED_SLASHES));

    file_put_contents($tmp.'/layers/b.json', json_encode([
        'id' => 'b',
        'meta' => ['title' => 'B'],
        'project' => ['allowed' => ['other']],
        'context' => ['roles' => ['geology/stratigraphy']],
        'providers' => ['features' => ['type'=>'arcgis-rest','url'=>'u','layerId'=>0]],
    ], JSON_UNESCAPED_SLASHES));

    $repo = new LayerRepository(new JsonLayerGateway($tmp), new LayerFactory());
    $layers = $repo->findByContext('geochron');
    $ids = array_map(fn($l) => $l['id'], $layers);

    expect($ids)->toBe(['a']);
});
