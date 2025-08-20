<?php
// src/Infrastructure/Repository/LayerRepository.php
namespace Gis14\Layers\Infrastructure\Repository;

use Gis14\Layers\Infrastructure\Factory\LayerFactoryInterface;
use Gis14\Layers\Infrastructure\Gateway\LayerGatewayInterface;

final class LayerRepository implements LayerRepositoryInterface
{
    public function __construct(
        private LayerGatewayInterface $gateway,
        private LayerFactoryInterface $factory
    ) {}

    public function findByContext(string $context): array
    {
        $out = [];
        foreach ($this->gateway->list($context) as $raw) {
            $out[] = $this->factory->make($raw, $context);
        }
        return $out;
    }

    public function findOne(string $context, string $id): ?array
    {
        $raw = $this->gateway->get($context, $id);
        return $raw ? $this->factory->make($raw, $context) : null;
    }
}
