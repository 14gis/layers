<?php
// src/Infrastructure/Factory/LayerFactoryInterface.php
namespace Gis14\Layers\Infrastructure\Factory;

/**
 * Creates normalized runtime items from raw docs.
 */
interface LayerFactoryInterface
{
    /** @return array<string,mixed> Normalized layer */
    public function make(array $raw, string $context): array;
}