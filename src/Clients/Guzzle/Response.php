<?php

declare (strict_types=1);

namespace AdityaZanjad\Http\Clients\Guzzle;

use AdityaZanjad\Http\Interfaces\HttpResponse;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

use function AdityaZanjad\Http\Utils\arr_first;

/**
 * @version 1.0
 */
class Response implements HttpResponse
{
    /**
     * @var \GuzzleHttp\Psr7\Response $response
     */
    protected GuzzleResponse $response;

    /**
     * @param \GuzzleHttp\Psr7\Response $response
     */
    public function __construct(GuzzleResponse $response)
    {
        $this->response = $response;
    }

    /**
     * @inheritDoc
     */
    public function code(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @inheritDoc
     */
    public function status(): string
    {
        return \strtoupper($this->response->getReasonPhrase());
    }

    /**
     * @inheritDoc
     */
    public function header(string $name): null|string|array
    {
        $header = $this->response->getHeader($name);

        if (is_string($header) || count($header) > 1) {
            return $header;
        }

        return arr_first($header);
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
    public function body(array $options = []): mixed
    {
        /**
         * First, we'll assume that the received response's body is in JSON format & then, attempt
         * to 'json_decode()' & return it an array. However, if the body is not the JSON one,
         * we'll return the received response's body as it is without performing any
         * operation on it.
         */
        $body               =   (string) $this->response->getBody();
        $decodedJsonBody    =   json_decode($body, true, $options['json']['depth'] ?? 512);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decodedJsonBody;
        }

        if (empty($body)) {
            return null;
        }

        return $body;
    }
}
