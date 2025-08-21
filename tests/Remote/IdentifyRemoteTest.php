<?php
use Tests\Support\TestServer;

it('can query arcgis via proxy (remote)', function () {
    //skipIfNoRemote();

    $server = TestServer::start(
        __DIR__ . '/../../public',
        null,
        [
            'UPSTREAM_MODE'        => 'record',
            'UPSTREAM_RECORD_DIR'  => __DIR__.'/../../var/cassettes',
            'LAYERS_DEBUG_LOG'     => __DIR__.'/../../var/logs/layers-debug.log',
            'LAYERS_COMPILED_DIR'  => __DIR__.'/../../compiled/schema', // falls benötigt
        ],
        '/v1/api/capabilities?context=geochron' // ✅ sinnvoller Probe-Pfad
    );

    $base   = $server->baseUrl();

    $url = $base.'/v1/proxy/geochron/guek250/0/query?f=json&geometry={"x":13.4,"y":52.5}&geometryType=esriGeometryPoint&inSR=4326&spatialRel=esriSpatialRelIntersects&outFields=*&returnGeometry=false';

    $res = file_get_contents($url);
    expect($http_response_header[0])->toContain('200');
    $json = json_decode($res, true);
    expect($json)->toBeArray();
    expect($json)->toHaveKey('features');
})->group('remote');
