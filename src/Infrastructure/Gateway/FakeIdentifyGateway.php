<?php
// src/Infrastructure/Gateway/FakeIdentifyGateway.php
namespace Gis14\Layers\Infrastructure\Gateway;

use Gis14\Layers\Util\Dot;

final class FakeIdentifyGateway implements IdentifyGatewayInterface
{
    public function identifyPoint(array $layer, float $x, float $y, string $sr = 'EPSG:4326'): array
    {
        // Use context.bbox from raw if available
        $raw = $layer['raw'] ?? [];
        $bbox = Dot::get($raw, 'context.bbox');
        $inside = false;
        if (is_array($bbox) && isset($bbox[0][0], $bbox[0][1], $bbox[1][0], $bbox[1][1])) {
            [$minLon, $minLat] = $bbox[0];
            [$maxLon, $maxLat] = $bbox[1];
            $inside = ($x >= $minLon && $x <= $maxLon && $y >= $minLat && $y <= $maxLat);
        }

        if (!$inside) {
            return ['features' => []];
        }

        // Build deterministic fake properties from identify.fields
        $fields = Dot::get($raw, 'identify.fields') ?? [];
        $props = [];
        foreach ($fields as $f) {
            $props[$f] = "fake:{$f}";
        }
        $props['__sr'] = $sr;
        $props['__layer'] = $layer['id'] ?? null;

        return [
            'features' => [[
                'id' => 1,
                'geometry' => ['type' => 'Point', 'coordinates' => [$x, $y]],
                'properties' => $props,
            ]],
        ];
    }
}
