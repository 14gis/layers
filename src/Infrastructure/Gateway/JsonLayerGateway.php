<?php
// src/Infrastructure/Gateway/JsonLayerGateway.php
namespace Gis14\Layers\Infrastructure\Gateway;

use Gis14\Layers\Util\Dot;

final class JsonLayerGateway implements LayerGatewayInterface
{
    public function __construct(private string $compiledBaseDir) {}

    public function list(string $context): array
    {
        $dir = rtrim($this->compiledBaseDir, '/').'/layers';
        if (!is_dir($dir)) return [];
        $out = [];
        foreach (glob($dir.'/*.json') as $file) {
            $doc = $this->decode($file);
            $contexts = (array)(Dot::get($doc, 'project.allowed') ?? []);
            if (!in_array($context, $contexts, true)) continue;
            $out[] = $doc;
        }
        return $out;
    }

    public function get(string $context, string $id): ?array
    {
        $file = rtrim($this->compiledBaseDir, '/')."/layers/{$id}.json";
        if (!is_file($file)) return null;
        $doc = $this->decode($file);
        $contexts = (array)(Dot::get($doc, 'project.allowed') ?? []);
        return in_array($context, $contexts, true) ? $doc : null;
    }

    private function decode(string $file): array
    {
        $json = file_get_contents($file) ?: '{}';
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }
}
