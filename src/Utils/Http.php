<?php

declare(strict_types=1);

namespace AdityaZanjad\Http\Utils;

use Exception;
use AdityaZanjad\Http\Interfaces\HttpClient;
use AdityaZanjad\Http\Adapters\CurlHttpAdapter;

/**
 * Initialize the HTTP Client Adapter.
 *
 * @param   array<string, mixed> $config
 * 
 * @return  \AdityaZanjad\Http\Interfaces\HttpClient
 */
function http(array $config = []): HttpClient
{
    return match ($config['adapter'] ?? 'curl') {
        'curl'  =>  new CurlHttpAdapter($config),
        default =>  throw new Exception("[Developer][Exception]: The HTTP adapter name {$config['adapter']} is invalid.")
    };
}
