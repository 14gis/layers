<?php
// Purpose: run bin/compile.php against a temp schema folder containing a broken layer
it('compile fails on invalid layer (missing features.url)', function () {
    $base = sys_get_temp_dir() . '/layers-compile-' . bin2hex(random_bytes(4));
    $src  = $base . '/schema';
    $dst  = $base . '/compiled';
    @mkdir($src . '/layers', 0777, true);

    // Provide schema version file
    file_put_contents($src . '/SCHEMA_VERSION', "1.0.0\n");

    // Minimal broken YAML (features.url missing)
    $yaml = <<<'YAML'
id: broken
meta:
  title: "Broken Layer"
project:
  allowed: [geochron]
context:
  roles: [geology/stratigraphy]
providers:
  features:
    type: arcgis-rest
    layerId: 0
YAML;

    file_put_contents($src . '/layers/broken.yaml', $yaml);

    // Call compiler with explicit arguments; capture output and exit code
    $cmd = sprintf(
        'php bin/compile.php %s %s %s 2>&1',
        escapeshellarg($src),
        escapeshellarg($dst),
        escapeshellarg($src . '/SCHEMA_VERSION')
    );

    exec($cmd, $out, $code);
    $text = implode("\n", $out);

    expect($code)->toBe(1);
    expect($text)->toContain('ERROR:');
    expect($text)->toContain('layers/broken.yaml');
    expect($text)->toContain('/providers/features/url');
});
