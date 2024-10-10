<?php

namespace AdityaZanjad\Http;

use Exception;
use AdityaZanjad\Http\Enums\Client;
use AdityaZanjad\Http\Interfaces\HttpClient;

class Http
{
    /**
     * Send a new HTTP request based on the given array data.
     *
     * @param array<string, mixed> $request => The request data containing all the necessary fields required to make the HTTP request.
     *
     * @return \AdityaZanjad\Http\Interfaces\HttpClient
     */
    public static function make(array $request): HttpClient
    {
        // Get the name of the client & its associated class to make the HTTP request.
        $client         =   $request['client'] ?? 'stream';
        $clientClass    =   Client::tryFromName($client);

        if (is_null($client)) {
            throw new Exception("[Developer][Exception]: The HTTP client [{$client}] is either invalid OR not supported.");
        }

        // Instantiate the HTTP client class & make a new HTTP request.
        return (new $clientClass($request))->send();
    }
}
