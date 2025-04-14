<?php

declare (strict_types=1);

namespace AdityaZanjad\Http;

use AdityaZanjad\Http\Enums\Provider;
use AdityaZanjad\Http\Interfaces\HttpProvider;
use AdityaZanjad\Http\Interfaces\HttpResponse;

/**
 * @version 2.0
 */
class Http
{
    /**
     * @var \AdityaZanjad\Http\Interfaces\HttpClient $provider
     */
    protected HttpProvider $provider;

    /**
     * @param string $provider
     */
    public function __construct(string $provider = 'auto')
    {
        $provider       =   Provider::valueOf($provider);
        $this->provider =   new $provider();
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
        return $this->provider->send($data);
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
        return $this->provider->pool($data);
    }
}
