<?php
// src/Infrastructure/Gateway/LayerGatewayInterface.php
namespace Gis14\Layers\Infrastructure\Gateway;

/**
 * Provides raw layer documents. No normalization here.
 */
interface LayerGatewayInterface
{
    /** @return array<int,array<string,mixed>> Raw docs for context */
    public function list(string $context): array;

    /** @return array<string,mixed>|null Raw doc or null */
    public function get(string $context, string $id): ?array;
}
