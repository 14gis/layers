<?php
// tests/E2E/CapabilitiesHttpTest.php
// Purpose: start built-in PHP server, hit HTTP endpoint, assert response.

// Helper: find an available TCP port on localhost
function find_free_port(): int {
    $sock = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if (!$sock) {
        throw new RuntimeException("Cannot allocate port: $errstr");
    }
    $name = stream_socket_get_name($sock, false); // e.g. 127.0.0.1:54321
    fclose($sock);
    return (int)substr(strrchr($name, ':'), 1);
}

// Global state for this file's tests
$GLOBALS['__server_proc'] = null;
$GLOBALS['__server_port'] = null;

beforeAll(function () {
    // 1) Ensure compiled artifacts exist
    $cmd = 'php bin/compile.php ./schema ./compiled/schema ./schema/SCHEMA_VERSION 2>&1';
    exec($cmd, $out, $code);
    if ($code !== 0) {
        throw new RuntimeException("Compile failed:\n".implode("\n", $out));
    }

    // 2) Pick a free port and start the built-in server
    $port = find_free_port();
    $docroot = __DIR__ . '/../..' . '/public';
    $env = [
        'LAYERS_COMPILED_DIR' => realpath(__DIR__.'/../..').'/compiled/schema',
    ];

    $log = sys_get_temp_dir().'/php-server-'.bin2hex(random_bytes(3)).'.log';
    $cmd = sprintf('php -S 127.0.0.1:%d -t %s', $port, escapeshellarg($docroot));

    $desc = [
        0 => ['pipe', 'r'],
        1 => ['file', $log, 'a'],
        2 => ['file', $log, 'a'],
    ];
    $proc = proc_open($cmd, $desc, $pipes, getcwd(), $env);
    if (!is_resource($proc)) {
        throw new RuntimeException('Failed to start PHP built-in server');
    }

    // 3) Wait until the endpoint responds (simple polling)
    $ready = false;
    $url = sprintf('http://127.0.0.1:%d/v1/api/capabilities?context=geochron', $port);
    for ($i = 0; $i < 50; $i++) { // ~5 seconds max
        $ctx = stream_context_create(['http' => ['timeout' => 0.2]]);
        $res = @file_get_contents($url, false, $ctx);
        if ($res !== false) { $ready = true; break; }
        usleep(100000);
    }
    if (!$ready) {
        proc_terminate($proc);
        proc_close($proc);
        throw new RuntimeException('Server did not become ready in time');
    }

    $GLOBALS['__server_proc'] = $proc;
    $GLOBALS['__server_port'] = $port;
});

afterAll(function () {
    if (is_resource($GLOBALS['__server_proc'])) {
        @proc_terminate($GLOBALS['__server_proc']);
        @proc_close($GLOBALS['__server_proc']);
    }
});

it('serves capabilities for geochron', function () {
    $port = $GLOBALS['__server_port'];
    $url = sprintf('http://127.0.0.1:%d/v1/api/capabilities?context=geochron', $port);

    $json = file_get_contents($url);
    expect($json)->not->toBeFalse();

    $data = json_decode($json, true);
    expect($data['context'])->toBe('geochron');

    $ids = array_map(fn($l) => $l['id'] ?? null, $data['layers']);
    expect($ids)->toContain('guek250');
});

it('returns empty layers for unknown context', function () {
    $port = $GLOBALS['__server_port'];
    $url = sprintf('http://127.0.0.1:%d/v1/api/capabilities?context=unknown', $port);

    $json = file_get_contents($url);
    expect($json)->not->toBeFalse();

    $data = json_decode($json, true);
    expect($data['layers'])->toBeArray()->toHaveLength(0);
});
