<?php

namespace AdityaZanjad\Http;

use GuzzleHttp\Client;
use AdityaZanjad\Http\Clients\Curl;
use AdityaZanjad\Http\Clients\Guzzle;
use AdityaZanjad\Http\Clients\Stream;
use AdityaZanjad\Http\Interfaces\HttpClient;

/**
 * Make a HTTP request based on the given data & return its response.
 *
 * @param array<string, mixed> $data
 *
 * @return \AdityaZanjad\Http\Interfaces\HttpClient
 */
function http(array $data): HttpClient
{
    if (class_exists(Client::class)) {
        return new Guzzle($data);
    }

    if (extension_loaded('curl')) {
        return new Curl($data);
    }

    return new Stream($data);
}
