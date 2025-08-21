<?php
namespace Gis14\Layers\Infrastructure\Http\Upstream;

final class SimpleJsonLogger
{
    public function __construct(private ?string $file = null) {}

    public function log(string $level, array $data): void
    {
        $line = json_encode(['ts'=>date('c'),'level'=>$level]+$data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($this->file) {
            error_log($line.PHP_EOL, 3, $this->file);
        } else {
            error_log($line);
        }
    }
}
