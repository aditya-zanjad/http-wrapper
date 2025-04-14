<?php

declare (strict_types=1);

namespace AdityaZanjad\Http\Traits;

use AdityaZanjad\Http\Http;
use AdityaZanjad\Http\Interfaces\HttpResponse;

/**
 * @version 2.0
 */
trait HttpTrait
{
    /**
     * Create an instance of a HTTP client by the given name.
     *
     * @param array<string, mixed> $data
     *
     * @return \AdityaZanjad\Http\Interfaces\HttpResponse
     */
    final protected function http(array $data): HttpResponse
    {
        $provider   =   $data['provider'] ?? 'auto';
        $http       =   new Http($provider);

        unset($data['provider']);
        return $http->send($data);
    }

    /**
     * Make more than one concurrent HTTP requests.
     *
     * @param array<int|string, array<string, mixed>> $data
     *
     * @return array<int|string, \AdityaZanjad\Http\Interfaces\HttpResponse>
     */
    final protected function pool(array $data): array
    {
        return (new Http($data['provider'] ?? 'auto'))->pool($data);
    }

    /**
     * Obtain the instance of the HTTP Wrapper class.
     *
     * @param string $provider
     *
     * @return \AdityaZanjad\Http\Http
     */
    final protected function httpProvider(string $provider): Http
    {
        return new Http($provider);
    }
}
