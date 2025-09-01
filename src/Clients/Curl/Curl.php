<?php

declare(strict_types=1);

namespace AdityaZanjad\Http\Clients\Curl;

use AdityaZanjad\Http\Interfaces\HttpClient;
use AdityaZanjad\Http\Interfaces\HttpResponse;

/**
 * @version 1.0
 */
class Curl implements HttpClient
{
    /**
     * @inheritDoc
     */
    public function send(array $data): HttpResponse
    {
        // Prepare everything required for making a HTTP request.
        $curlRequest                        =   new Request($data);
        $request                            =   $curlRequest->build();
        $curl                               =   curl_init();
        $headers                            =   new ResponseHeaders();
        $request[CURLOPT_HEADERFUNCTION]    =   [$headers, 'process'];

        // Set the HTTP request, obtain its response & then, close the connection.
        curl_setopt_array($curl, $request);
        $response = curl_exec($curl);
        $response = new Response($curl, $headers->all(), $response);
        curl_close($curl);

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function pool(array $data): array
    {
        $multiCurl = curl_multi_init();

        return [];
    }
}
