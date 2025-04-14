<?php

declare (strict_types=1);

namespace AdityaZanjad\Http\Clients\Guzzle;

use GuzzleHttp\Pool as GuzzlePool;
use GuzzleHttp\Client as GuzzleClient;
use AdityaZanjad\Http\Clients\Guzzle\Request;
use AdityaZanjad\Http\Interfaces\HttpProvider;
use AdityaZanjad\Http\Clients\Guzzle\Response;
use AdityaZanjad\Http\Interfaces\HttpResponse;

/**
 * @version 1.0
 */
class Guzzle implements HttpProvider
{
    /**
     * @inheritDoc
     */
    public function send(array $data): HttpResponse
    {
        $request        =   new Request($data);
        $guzzleClient   =   new GuzzleClient(['http_errors' => false]);
        $guzzleResponse =   $guzzleClient->send($request->build());

        return new Response($guzzleResponse);
    }

    /**
     * @inheritDoc
     */
    public function pool(array $data): array
    {
        $guzzleRequests     =   array_map(fn ($req) => (new Request($req))->build(), $data);
        $guzzleClient       =   new GuzzleClient();
        $guzzlePool         =   new GuzzlePool($guzzleClient, $guzzleRequests);
        $guzzleResponses    =   $guzzlePool->promise()->wait();
        $responses          =   array_map(fn ($res) => new Response($res), $guzzleResponses);

        return $responses;
    }
}
