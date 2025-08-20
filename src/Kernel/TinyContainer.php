<?php // src/Kernel/TinyContainer.php
namespace Gis14\Layers\Kernel;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class TinyContainer implements ContainerInterface
{
    private array $definitions = [];
    private array $instances = [];

    public function set(string $id, callable $factory): void
    { $this->definitions[$id] = $factory; }

    public function get(string $id)
    {
        if (isset($this->instances[$id])) return $this->instances[$id];
        if (!isset($this->definitions[$id])) {
            throw new class("No entry '$id'") extends \RuntimeException implements NotFoundExceptionInterface {};
        }
        return $this->instances[$id] = ($this->definitions[$id])($this);
    }

    public function has(string $id): bool
    { return isset($this->definitions[$id]) || isset($this->instances[$id]); }
}