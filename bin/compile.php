#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Gis14\Layers\Command\CompileSchemaCommand;

// --- Default paths ---
$defaultSource = __DIR__ . '/../schema';
$defaultTarget = __DIR__ . '/../compiled/schema';
$defaultSchemaVersion = __DIR__ . '/../schema/SCHEMA_VERSION';

// --- Simple CLI arg handling ---
$sourceDir = $argv[1] ?? $defaultSource;
$targetDir = $argv[2] ?? $defaultTarget;
$schemaVersionFile = $argv[3] ?? $defaultSchemaVersion;

// --- Output ---
echo "Compiling schema...\n";
echo "Source: $sourceDir\n";
echo "Target: $targetDir\n";

echo is_file($schemaVersionFile)
    ? "SchemaVersion: " . trim(file_get_contents($schemaVersionFile)) . "\n"
    : "SchemaVersion: (not found at $schemaVersionFile)\n";

// --- Run command ---
$command = new CompileSchemaCommand($sourceDir, $targetDir, $schemaVersionFile);
$command->run();