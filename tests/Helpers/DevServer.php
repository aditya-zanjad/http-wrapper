<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Exception;
use Symfony\Component\Process\Process;

final class DevServer
{
    private Process $process;

    private string $serverPath;

    private string $baseUrl;

    public function __construct()
    {
        $this->serverPath = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'Server' . DIRECTORY_SEPARATOR . 'Paths';
    }

    public function startServer(string $host = '127.0.0.1', string $port = '8000', array $vars = []): void
    {
        if (empty($vars)) {
            $vars = null;
        }

        for ($i = 0; $i < 10; $i++) {
            $this->baseUrl = "{$host}:{$port}";
            $this->process = new Process(['php', '-S', $this->baseUrl, '-t', $this->serverPath], null, $vars);
            $this->process->setTimeout(5)->start();

            if ($this->process->isRunning()) {
                break;
            }

            $host++;
            $port++;
        }
    }

    public function getBaseUrl(): string
    {
        if (!isset($this->baseUrl)) {
            throw new Exception("[Developer][Exception]: Execute the method [startServer()] first before accessing the base url.");
        }

        return $this->baseUrl;
    }

    public function stopServer(): void
    {
        if (!isset($this->process)) {
            return;
        }

        $this->process->stop();
    }
}
