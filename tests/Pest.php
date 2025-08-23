<?php
// tests/Pest.php
define('PROJECT_ROOT', realpath(__DIR__ . '/..'));
define('COMPILED_DIR', PROJECT_ROOT . '/compiled/schema');

fwrite(STDERR, "[Pest.php] loaded\n");


require_once __DIR__ . '/Support/Remote.php';

uses()->beforeAll(function () {
    static $done = false;         // verhindert Mehrfachlauf pro Prozess
    if ($done) return;
    $done = true;

    $cmd = sprintf(
        'php %s/bin/compile.php %s/schema %s/compiled/schema %s/schema/SCHEMA_VERSION 2>&1',
        PROJECT_ROOT, PROJECT_ROOT, PROJECT_ROOT, PROJECT_ROOT
    );
    exec($cmd, $out, $code);
    if ($code !== 0) {
        throw new RuntimeException("Compile failed:\n" . implode("\n", $out));
    }
})->in(__DIR__);

// Remote tests policy:
// - Default run: remote tests are skipped (see beforeEach below)
// - Opt‑in: RUN_REMOTE_TESTS=1 ./vendor/bin/pest --group=remote
uses()
    ->beforeEach(function () {
        if (in_array('remote', test()->groups(), true) && getenv('RUN_REMOTE_TESTS') !== '1') {
            test()->markTestSkipped('Remote tests are disabled. Set RUN_REMOTE_TESTS=1 to enable.');
        }
    })
    ->in(__DIR__.'/Remote');
