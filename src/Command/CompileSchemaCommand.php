<?php
// src/Command/CompileSchemaCommand.php

namespace Gis14\Layers\Command;

use Symfony\Component\Yaml\Yaml;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class CompileSchemaCommand
{
    protected string $sourceDir;
    protected string $targetDir;

    public function __construct(string $sourceDir, string $targetDir)
    {
        $resolvedSource = realpath($sourceDir);
        if ($resolvedSource === false) {
            throw new \InvalidArgumentException("Source directory not found: $sourceDir");
        }

        $this->sourceDir = $resolvedSource;
        $this->targetDir = $targetDir;
    }

    public function run(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->sourceDir));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'yaml') continue;

            $relativePath = substr($file->getPathname(), strlen($this->sourceDir) + 1);
            $jsonPath = str_replace('.yaml', '.json', $relativePath);
            $targetPath = $this->targetDir . '/' . $jsonPath;

            $this->ensureDirectory(dirname($targetPath));

            if (!file_exists($targetPath) || filemtime($file) > filemtime($targetPath)) {
                $this->compile($file->getPathname(), $targetPath);
            } else {
                echo "⏩ Skipped (up to date): $relativePath\n";
            }
        }
    }

    private function compile(string $sourcePath, string $targetPath): void
    {
        $yaml = Yaml::parseFile($sourcePath);
        $json = json_encode($yaml, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($targetPath, $json);
        echo "✔ Compiled: $sourcePath → $targetPath\n";
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
}
