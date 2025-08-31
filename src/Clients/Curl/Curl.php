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
        // Gather everything required to make the HTTP request.
        $curlRequest                        =   new Request($data);
        $request                            =   $curlRequest->build();
        $curl                               =   curl_init();
        $headers                            =   new ResponseHeaders();
        $request[CURLOPT_HEADERFUNCTION]    =   [$headers, 'process'];

        // Set all the necessary HTTP request options required to send the HTTP request.
        curl_setopt_array($curl, $request);

        // Set the HTTP request, obtain its response & then, close the connection.
        $response = curl_exec($curl);
        curl_close($curl);

        return new Response($curl, $headers->all(), $response);
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
