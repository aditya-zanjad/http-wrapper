<?php

namespace AdityaZanjad\Http\Clients\Stream;

use Exception;
use AdityaZanjad\Http\Interfaces\HttpProvider;
use AdityaZanjad\Http\Interfaces\HttpResponse;

/**
 * @version 1.0
 */
class Stream implements HttpProvider
{
    /**
     * @inheritDoc
     */
    public function send(array $data): HttpResponse
    {
        // Prepare the HTTP request data before making the actual HTTP request.
        $request        =   new Request($data);
        $requestData    =   $request->build();
        $streamContext  =   stream_context_create(['http' => $requestData['http']]);

        // Make the HTTP request & obtain its response. Extract HTTP headers & body.
        $httpStream =   fopen($requestData['url'], 'r', false, $streamContext);
        $headers    =   get_headers($requestData['url'], true, $streamContext) ?: [];
        $body       =   stream_get_contents($httpStream);

        // Close the HTTP stream & return the response.
        fclose($httpStream);
        return new Response($headers, $body);
    }

    /**
     * @inheritDoc
     */
    public function pool(array $data): array
    {
        throw new Exception("[Developer][Exception]: The provider [" . static::class . "] currently does not support concurrent HTTP requests.");
    }
}
