<?php

namespace AdityaZanjad\Http\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use AdityaZanjad\Http\Builders\GuzzleRequest;
use AdityaZanjad\Http\Interfaces\HttpClient;


class Guzzle implements HttpClient
{
    /**
     * @var \GuzzleHttp\Client $client
     */
    protected Client $client;
    
    /**
     * @var \GuzzleHttp\Psr7\Response $response
     */
    protected Response $response;

    /**
     * Inject necessary data into the class.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(protected array $data)
    {
        $this->client   =   new Client();
        $this->response =   $this->client->send((new GuzzleRequest($data))->build());
    }

    /**
     * @inheritDoc
     */
    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @inheritDoc
     */
    public function phrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    /**
     * @inheritDoc
     */
    public function decode(): mixed
    {
        $body = (string) $this->response->getBody();

        if (json_validate($body)) {
            return json_decode($body);
        }

        return $body;
    }

    /**
     * @inheritDoc
     */
    public function headers(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * @inheritDoc
     */
    public function header(string $key): mixed
    {
        return $this->response->getHeader($key);
    }
}
