<?php

namespace AdityaZanjad\Http;

use Exception;
use AdityaZanjad\Http\Enums\Client;
use AdityaZanjad\Http\Interfaces\HttpClient;

class Http
{
    /**
     * Send a new HTTP request.
     *
     * @param array<string, mixed> $data
     * 
     * @return \AdityaZanjad\Http\Interfaces\HttpClient
     */
    public static function send(array $data): HttpClient
    {
        $data['client'] = Client::tryFromName($data['client'] ?? 'stream');

        if (is_null($data['client'])) {
            throw new Exception("[Developer][Exception]: The HTTP client [{$data['client']}] is either invalid OR not supported.");
        }

        return (new $data['client'])($data)->send();
    }
}
