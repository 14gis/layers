<?php
// src/Command/CompileSchemaCommand.php
namespace Gis14\Layers\Command;

use Symfony\Component\Yaml\Yaml;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Gis14\Layers\Validation\SchemaValidator;
use Gis14\Layers\Util\Dot;

class CompileSchemaCommand
{
    protected string $sourceDir;
    protected string $targetDir;
    protected ?string $schemaVersionFile;

    /** @var array<string, array<string,bool>> */
    private array $seenIdsByContext = [];

    public function __construct(string $sourceDir, string $targetDir, ?string $schemaVersionFile = null)
    {
        $resolvedSource = realpath($sourceDir);
        if ($resolvedSource === false) {
            throw new \InvalidArgumentException("Source directory not found: $sourceDir");
        }
        $this->sourceDir = $resolvedSource;
        $this->targetDir = $targetDir;
        $this->schemaVersionFile = $schemaVersionFile;
    }

    public function run(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->sourceDir));
        $errorsTotal = 0;

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) continue;
            $ext = strtolower($file->getExtension());
            if ($ext !== 'yaml' && $ext !== 'yml') continue;

            $relativePath = substr($file->getPathname(), strlen($this->sourceDir) + 1);
            // output mirrors input path, just .yaml -> .json
            $jsonPath = preg_replace('/\.(yaml|yml)$/i', '.json', $relativePath);
            $targetPath = rtrim($this->targetDir, '/').'/'.$jsonPath;

            try {
                $data = Yaml::parseFile($file->getPathname());
                if (!is_array($data)) $data = [];
            } catch (\Throwable $e) {
                $this->stderr("❌ YAML parse error: {$relativePath}: {$e->getMessage()}");
                $errorsTotal++;
                continue;
            }

            // Validate structure
            $errors = SchemaValidator::validate($data);
            $errors = array_merge($errors, $this->validateSemantics($data));
            if ($errors) {
                foreach ($errors as $err) {
                    $this->stderr("❌ {$relativePath}\n  path: {$err['path']}\n  error: {$err['message']}");
                }
                $errorsTotal += count($errors);
                continue; // skip writing
            }

            // Inject versions (schemaVersion from file; contentVersion from meta.version)
            $schemaVersion = $this->readSchemaVersion();
            if ($schemaVersion !== null) {
                Dot::set($data, 'meta.schemaVersion', $schemaVersion);
            }
            $contentVersion = Dot::get($data, 'meta.contentVersion');
            if ($contentVersion === null) {
                $v = Dot::get($data, 'meta.version');
                if (is_string($v) && $v !== '') {
                    Dot::set($data, 'meta.contentVersion', $v);
                }
            }

            $this->ensureDirectory(dirname($targetPath));
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            file_put_contents($targetPath, $json);
            echo "✔ Compiled: {$relativePath} → {$targetPath}\n";
        }

        if ($errorsTotal > 0) {
            $this->stderr("Build failed with {$errorsTotal} error(s).");
            exit(1);
        }
    }

    /** @return list<array{path:string,message:string}> */
    private function validateSemantics(array $doc): array
    {
        $errs = [];
        $id = (string)($doc['id'] ?? '');
        $contexts = (array)(Dot::get($doc, 'project.allowed') ?? []);
        foreach ($contexts as $ctx) {
            if (!is_string($ctx) || $ctx === '') continue;
            $this->seenIdsByContext[$ctx] ??= [];
            if (isset($this->seenIdsByContext[$ctx][$id])) {
                $errs[] = ['path' => '/project/allowed', 'message' => "duplicate id '{$id}' in context '{$ctx}'"];
            } else {
                $this->seenIdsByContext[$ctx][$id] = true;
            }
        }
        return $errs;
    }

    private function readSchemaVersion(): ?string
    {
        $f = $this->schemaVersionFile;
        if ($f && is_file($f)) return trim((string)file_get_contents($f));
        // fallback: sibling SCHEMA_VERSION next to source dir
        $fallback = rtrim($this->sourceDir, '/').'/../SCHEMA_VERSION';
        if (is_file($fallback)) return trim((string)file_get_contents($fallback));
        return null;
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    private function stderr(string $msg): void
    {
        fwrite(STDERR, $msg."\n");
    }
}