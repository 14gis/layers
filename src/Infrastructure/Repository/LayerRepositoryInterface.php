<?php
// src/Domain/LayerRepositoryInterface.php
namespace Gis14\Layers\Infrastructure\Repository;

interface LayerRepositoryInterface
{
    /** @return array<int,array<string,mixed>> */
    public function findByContext(string $context): array;

    /** @return array<string,mixed>|null */
    public function findOne(string $context, string $id): ?array;
}
