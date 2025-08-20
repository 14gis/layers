<?php
namespace Tests\Support;

/**
 * Tiny helper to start/stop PHP's built-in server for E2E tests.
 * - Uses proc_open to launch `php -S` with a temporary log file.
 * - Waits until a probe URL responds (HTTP 200/any body).
 * - Cleans up the process on shutdown.
 */
final class TestServer
{
    /** @var resource|null */
    private $proc = null;
    private ?int $port = null;
    private ?string $logFile = null;
    private string $host = '127.0.0.1';

    public static function start(string $docroot, ?int $port = null, array $env = [], string $probePath = '/'): self
    {
        $self = new self();
        $self->port = $port ?? self::findFreePort($self->host);
        $self->logFile = sys_get_temp_dir() . '/php-server-' . bin2hex(random_bytes(4)) . '.log';

        $cmd = sprintf('php -S %s:%d -t %s', $self->host, $self->port, escapeshellarg($docroot));
        $desc = [
            0 => ['pipe', 'r'],
            1 => ['file', $self->logFile, 'a'],
            2 => ['file', $self->logFile, 'a'],
        ];

        $proc = proc_open($cmd, $desc, $pipes, getcwd(), $env);
        if (!is_resource($proc)) {
            throw new \RuntimeException('Failed to start PHP built-in server');
        }
        $self->proc = $proc;

        // Ensure cleanup even if a test fails hard.
        register_shutdown_function(static function () use ($self): void {
            $self->stop();
        });

        $self->waitReady($probePath);
        return $self;
    }

    public function url(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        return sprintf('http://%s:%d%s', $this->host, $this->port, $path);
    }

    public function port(): int
    {
        return (int)$this->port;
    }

    public function isRunning(): bool
    {
        return is_resource($this->proc);
    }

    public function stop(): void
    {
        if (is_resource($this->proc)) {
            @proc_terminate($this->proc);
            @proc_close($this->proc);
            $this->proc = null;
        }
        if ($this->logFile && is_file($this->logFile)) {
            @unlink($this->logFile);
        }
    }

    public function logFile(): ?string
    {
        return $this->logFile;
    }

    private static function findFreePort(string $host): int
    {
        $sock = @stream_socket_server('tcp://' . $host . ':0', $errno, $errstr);
        if (!$sock) {
            throw new \RuntimeException('Cannot allocate port');
        }
        $name = stream_socket_get_name($sock, false); // e.g. 127.0.0.1:54321
        fclose($sock);
        $parts = explode(':', (string)$name);
        return (int)end($parts);
    }

    private function waitReady(string $path, float $timeoutSeconds = 5.0): void
    {
        $deadline = microtime(true) + $timeoutSeconds;
        $url = $this->url($path);
        $ctx = stream_context_create(['http' => ['timeout' => 0.2]]);
        while (microtime(true) < $deadline) {
            $res = @file_get_contents($url, false, $ctx);
            if ($res !== false) {
                return;
            }
            usleep(100000);
        }
        $this->stop();
        throw new \RuntimeException('Server did not become ready in time: ' . $url);
    }
}
