<?php

namespace AdityaZanjad\Http\Clients;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use AdityaZanjad\Http\Interfaces\HttpClient;
use AdityaZanjad\Http\Builders\GuzzleRequest;

class Guzzle implements HttpClient
{
    /**
     * @var \GuzzleHttp\Client $client
     */
    protected Client $client;

    /**
     * @var \GuzzleHttp\Psr7\Response
     */
    protected Response $res;

    /**
     * Inject necessary data into the class.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(protected array $data)
    {
        if (!class_exists(Client::class)) {
            throw new Exception("[Developer][Exception]: The library [guzzlehttp/guzzle] is required for the driver [{$data['client']}] to work.");
        }

        $this->data     =   (new GuzzleRequest($this->data))->build();
        $this->client   =   new Client();
    }

    /**
     * @inheritDoc
     */
    public function send(): static
    {
        try {
            $this->res = $this->client->send(
                new Request(
                    $this->data['method'],
                    $this->data['url'],
                    $this->data['headers'] ?? [],
                    $this->data['body'] ?? ''
                ),
                $this->data['options']
            );
        } catch (RequestException $e) {
            $this->res = $e->getResponse();
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function status(): string
    {
        return $this->res->getReasonPhrase();
    }

    /**
     * @inheritDoc
     */
    public function code(): int
    {
        return $this->res->getStatusCode();
    }

    /**
     * @inheritDoc
     */
    public function body(): mixed
    {
        $body = (string) $this->res->getBody();

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
        return $this->res->getHeaders();
    }

    /**
     * @inheritDoc
     */
    public function header(string $key): mixed
    {
        $header = $this->res->getHeader($key);

        if (empty($header)) {
            return null;
        }

        return $header[0];
    }
}
