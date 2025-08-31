<?php

declare (strict_types=1);

namespace AdityaZanjad\Http;

use AdityaZanjad\Http\Enums\Client;
use AdityaZanjad\Http\Interfaces\HttpClient;
use AdityaZanjad\Http\Interfaces\HttpResponse;

/**
 * @version 2.0
 */
class Http
{
    /**
     * @var \AdityaZanjad\Http\Interfaces\HttpClient $client
     */
    protected HttpClient $client;

    /**
     * @param string $provider
     */
    public function __construct(string $provider = 'auto')
    {
        $provider       =   Client::valueOf($provider);
        $this->client   =   new $provider();
    }

    /**
     * Send a single HTTP request & obtain its response.
     *
     * @param array<string, mixed> $data
     *
     * @return \AdityaZanjad\Http\Interfaces\HttpResponse
     */
    public function send(array $data): HttpResponse
    {
        return $this->client->send($data);
    }

    /**
     * Send concurrent bulk HTTP requests & obtain their responses.
     *
     * @param array<int|string, mixed> $data
     *
     * @return array<int|string, \AdityaZanjad\Http\Interfaces\HttpResponse>
     */
    public function pool(array $data): array
    {
        return $this->client->pool($data);
    }
}
