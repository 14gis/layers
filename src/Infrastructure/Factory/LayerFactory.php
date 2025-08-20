<?php
// src/Infrastructure/Factory/LayerFactory.php
namespace Gis14\Layers\Infrastructure\Factory;

use Gis14\Layers\Util\Dot;

final class LayerFactory implements LayerFactoryInterface
{
    public function make(array $raw, string $context): array
    {
        $id    = (string)($raw['id'] ?? '');
        $title = (string)(Dot::get($raw, 'meta.title') ?? $id);

        $semantic = (array)(Dot::get($raw, 'context.roles') ?? []);
        $technical = (array)(Dot::get($raw, 'layer.roles') ?? []);

        $feat = (array)(Dot::get($raw, 'providers.features') ?? []);
        $tiles = (array)(Dot::get($raw, 'providers.tiles') ?? []);

        $features = [
            'type'   => $feat['type']   ?? null,
            'url'    => $feat['url']    ?? null,
            'layers' => isset($feat['layerId']) ? [$feat['layerId']] : [],
            'supportsIdentify' => (bool)($feat['supportsIdentify'] ?? false),
        ];
        $tilesSrc = [
            'type'   => $tiles['type']   ?? null,
            'url'    => $tiles['url']    ?? null,
            'layers' => isset($tiles['layerId']) ? [$tiles['layerId']] : [],
        ];

        return [
            'id' => $id,
            'title' => $title,
            'contexts' => (array)(Dot::get($raw, 'project.allowed') ?? []),
            'semanticRoles' => $semantic,
            'technicalRoles' => $technical,
            'source' => [
                'tiles' => $tilesSrc,
                'features' => $features,
            ],
            'capabilities' => [
                'identify' => $features['supportsIdentify'] && is_array(Dot::get($raw, 'identify.fields')),
            ],
            'raw' => $raw,
            'context' => $context,
        ];
    }
}
