<?php
// tests/Unit/SchemaValidatorTest.php
use Gis14\Layers\Validation\SchemaValidator;

it('fails when features.url is missing', function () {
    $doc = [
        'id' => 'x',
        'meta' => ['title' => 'X'],
        'project' => ['allowed' => ['geochron']],
        'context' => ['roles' => ['geology/stratigraphy']],
        'providers' => ['features' => ['type' => 'arcgis-rest', 'layerId' => 0]],
    ];
    $errors = SchemaValidator::validate($doc);
    $paths = array_column($errors, 'path');
    expect($paths)->toContain('/providers/features/url');
});
