<?php

declare(strict_types=1);

namespace AdityaZanjad\Http\Adapters;

use AdityaZanjad\Http\Interfaces\HttpClient;
use AdityaZanjad\Http\Adapters\Curl\Request;
use AdityaZanjad\Http\Adapters\Curl\Response;
use AdityaZanjad\Http\Interfaces\HttpResponse;
use AdityaZanjad\Http\Adapters\Curl\ResponseHeaders;

/**
 * @version 1.0
 */
class CurlHttpAdapter implements HttpClient
{
    public function __construct(protected array $config = [])
    {
        //
    }

    public function send(array $data): HttpResponse
    {
        // Prepare everything required to make the HTTP request.
        $curl       =   \curl_init();
        $req        =   (new Request($data))->build();
        $headers    =   new ResponseHeaders();

        // Set the HTTP request options.
        $req[CURLOPT_HEADERFUNCTION] = [$headers, 'process'];
        \curl_setopt_array($curl, $req);

        // Send the HTTP request & obtain its response.
        $response = \curl_exec($curl);
        $response = new Response($curl, $headers->all(), $response);

        \curl_close($curl);
        return $response;
    }

    public function pool(array $data): array
    {
        return [];
    }
}
