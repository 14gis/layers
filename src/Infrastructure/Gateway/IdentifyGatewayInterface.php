<?php
// src/Domain/IdentifyGatewayInterface.php
namespace Gis14\Layers\Infrastructure\Gateway;

interface IdentifyGatewayInterface
{
    /**
     * Perform an identify at a point.
     * @param array $layer Normalized layer as returned by your repository/factory (we need bbox/identify fields).
     * @return array{features: array<int, array<string,mixed>>}
     */
    public function identifyPoint(array $layer, float $x, float $y, string $sr = 'EPSG:4326'): array;
}
