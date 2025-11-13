<?php

declare(strict_types=1);

namespace AdityaZanjad\Http;

use AdityaZanjad\Http\Interfaces\HttpClient;

use function AdityaZanjad\Http\Utils\http;

/**
 * @version 2.0
 */
class Http
{
    /**
     * Instantiate the HTTP Client Adapter for making the HTTP request.
     * 
     * @param array $config
     * 
     * @return \AdityaZanjad\Http\Interfaces\HttpClient
     */
    public static function create(array $config = []): HttpClient
    {
        return http($config);
    }
}
