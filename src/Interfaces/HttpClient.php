<?php

declare (strict_types=1);

namespace AdityaZanjad\Http\Interfaces;

/**
 * @version 2.0
 */
interface HttpClient
{
    /**
     * Send a single HTTP request & obtain its response.
     *
     * @param array<int|string, string|array<string, mixed>> $data
     *
     * @return \AdityaZanjad\Http\Interfaces\HttpResponse
     */
    public function send(array $data): HttpResponse;

    /**
     * Send more than one concurrent HTTP requests & obtain their responses.
     *
     * @param array<int, array<string, mixed>> $data
     *
     * @return array<int, \AdityaZanjad\Http\Interfaces\HttpClient>
     */
    public function pool(array $data): array;
}
