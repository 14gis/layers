<?php
// tests/Pest.php
define('PROJECT_ROOT', realpath(__DIR__ . '/..'));
define('COMPILED_DIR', PROJECT_ROOT . '/compiled/schema');

require_once __DIR__ . '/Support/Remote.php';

beforeAll(function () {
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
});
