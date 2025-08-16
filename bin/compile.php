#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Gis14\Layers\Command\CompileSchemaCommand;

// --- Default paths ---
$defaultSource = __DIR__ . '/../schema';
$defaultTarget = __DIR__ . '/../compiled/schema';

// --- Simple CLI arg handling ---
$sourceDir = $argv[1] ?? $defaultSource;
$targetDir = $argv[2] ?? $defaultTarget;

// --- Output ---
echo "Compiling schema...\n";
echo "Source: $sourceDir\n";
echo "Target: $targetDir\n";

// --- Run command ---
$command = new CompileSchemaCommand($sourceDir, $targetDir);
$command->run();
