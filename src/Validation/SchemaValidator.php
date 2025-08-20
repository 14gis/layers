<?php
// src/Validation/SchemaValidator.php
namespace Gis14\Layers\Validation;

use Gis14\Layers\Util\Dot;

final class SchemaValidator
{
    /** @return list<array{path:string,message:string}> */
    public static function validate(array $doc): array
    {
        $errors = [];
        $s = fn(string $p) => (string)Dot::get($doc, $p);

        // Required basics
        if (!is_string($doc['id'] ?? null) || $doc['id'] === '') {
            $errors[] = ['path' => '/id', 'message' => 'must be a non-empty string'];
        }
        if (!is_string($s('meta.title')) || $s('meta.title') === '') {
            $errors[] = ['path' => '/meta/title', 'message' => 'must be a non-empty string'];
        }

        // project.allowed
        $allowed = Dot::get($doc, 'project.allowed');
        if (!is_array($allowed) || count($allowed) < 1 || array_filter($allowed, fn($v) => !is_string($v))) {
            $errors[] = ['path' => '/project/allowed', 'message' => 'must be array<string> with at least 1 item'];
        }

        // context.roles
        $roles = Dot::get($doc, 'context.roles');
        if (!is_array($roles) || count($roles) < 1 || array_filter($roles, fn($v) => !is_string($v))) {
            $errors[] = ['path' => '/context/roles', 'message' => 'must be array<string> with at least 1 item'];
        }

        // providers.features
        $ft = $s('providers.features.type');
        if ($ft === '') {
            $errors[] = ['path' => '/providers/features/type', 'message' => 'must be a non-empty string'];
        } elseif ($ft !== 'arcgis-rest') {
            $errors[] = ['path' => '/providers/features/type', 'message' => 'must equal "arcgis-rest" (MVP)'];
        }

        if (!is_string($s('providers.features.url')) || $s('providers.features.url') === '') {
            $errors[] = ['path' => '/providers/features/url', 'message' => 'must be a non-empty string'];
        }
        $layerId = Dot::get($doc, 'providers.features.layerId');
        if (!is_int($layerId) || $layerId < 0) {
            $errors[] = ['path' => '/providers/features/layerId', 'message' => 'must be integer >= 0'];
        }

        // Conditional identify
        $supportsIdentify = Dot::get($doc, 'providers.features.supportsIdentify');
        if ($supportsIdentify === true) {
            $fields = Dot::get($doc, 'identify.fields');
            if (!is_array($fields) || count($fields) < 1 || array_filter($fields, fn($v) => !is_string($v))) {
                $errors[] = ['path' => '/identify/fields', 'message' => 'must be array<string> with at least 1 item'];
            }
        }

        return $errors;
    }
}