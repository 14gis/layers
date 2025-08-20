<?php
// tests/E2E/CapabilitiesHttpTest.php
use Tests\Support\TestServer;

beforeAll(function () {
    // Ensure compiled artifacts exist (build step)
    $cmd = 'php bin/compile.php ./schema ./compiled/schema ./schema/SCHEMA_VERSION 2>&1';
    exec($cmd, $out, $code);
    if ($code !== 0) {
        throw new RuntimeException("Compile failed:\n" . implode("\n", $out));
    }

    $projectRoot = realpath(__DIR__ . '/../..');
    $docroot = $projectRoot . '/public';
    $env = [
        'LAYERS_COMPILED_DIR' => $projectRoot . '/compiled/schema',
    ];

    // Probe a cheap endpoint to know the server is ready
    $GLOBALS['__server'] = TestServer::start($docroot, null, $env, '/v1/api/capabilities?context=geochron');
});

afterAll(function () {
    if (isset($GLOBALS['__server'])) {
        $GLOBALS['__server']->stop();
    }
});

it('serves capabilities for geochron', function () {
    $server = $GLOBALS['__server'];
    $url = $server->url('/v1/api/capabilities?context=geochron');

    $json = file_get_contents($url);
    expect($json)->not->toBeFalse();

    $data = json_decode($json, true);
    expect($data['context'])->toBe('geochron');
    expect($data['layers'])->toBeArray();

    $ids = array_map(fn($l) => $l['id'] ?? null, $data['layers']);
    expect($ids)->toContain('guek250');
});

it('returns empty layers for unknown context', function () {
    $server = $GLOBALS['__server'];
    $url = $server->url('/v1/api/capabilities?context=unknown');

    $json = file_get_contents($url);
    expect($json)->not->toBeFalse();

    $data = json_decode($json, true);
    expect($data['layers'])->toBeArray()->toHaveLength(0);
});
