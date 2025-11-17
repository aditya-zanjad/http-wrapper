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
        $curlMulti  =   \curl_multi_init();
        $curls      =   [];
        $headers    =   [];

        // Prepare CURL Handles for making the HTTP requests.
        foreach ($data['requests'] as $label => $request) {
            $curl                           =   curl_init();
            $req                            =   (new Request($request))->build();
            $headers                        =   new ResponseHeaders();
            $req[CURLOPT_HEADERFUNCTION]    =   [$headers, 'process'];

            \curl_setopt_array($curl, $req);
            \curl_multi_add_handle($curlMulti, $curl);

            $curls[$label]      =   $curl;
            $headers[$label]    =   $headers;
        }

        // Execute CURL handles & obtain their responses.
        $remainingRequests = 1;

        while ($remainingRequests > 1) {
            \curl_multi_exec($curlMulti, $remainingRequests);
            \curl_multi_select($curlMulti, 1);
        }

        // Collect & return the CURL HTTP responses.
        $responses = [];

        foreach ($curls as $label => $curl) {
            $responses[$label] = new Response($curl, $headers[$label], curl_multi_getcontent($curl));

            \curl_multi_remove_handle($curlMulti, $curl);
            \curl_close($curl);
        }

        \curl_multi_close($curlMulti);
        return $responses;
    }
}
