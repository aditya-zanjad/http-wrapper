<?php

declare(strict_types=1);

namespace AdityaZanjad\Http\Adapters\Curl;

use CurlHandle;
use AdityaZanjad\Http\Enums\ResponseStatus;
use AdityaZanjad\Http\Interfaces\HttpResponse;

/**
 * @version 1.0
 */
class Response implements HttpResponse
{
    /**
     * HTTP Response status code.
     *
     * @var int $code
     */
    protected int $code;

    /**
     * HTTP response status reason phrase.
     *
     * @var string $status
     */
    protected string $status;

    /**
     * HTTP response body.
     *
     * @var mixed
     */
    protected mixed $body;

    /**
     * @param   mixed                                           $curl
     * @param   array<int|string, string|array<int, string>>    $headers
     * @param   bool|string                                     $response
     */
    public function __construct(protected CurlHandle $curl, protected array $headers, protected mixed $response)
    {
        $this->code     =   curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->status   =   ResponseStatus::keyOf($this->code);
    }

    /**
     * @inheritDoc
     */
    public function code(): int
    {
        return $this->code;
    }

    /**
     * @inheritDoc
     */
    public function status(): string
    {
        return $this->status;
    }

    /**
     * @inheritDoc
     */
    public function header(string $name): null|string|array
    {
        $loweredName = \strtolower($name);

        foreach ($this->headers as $headerName => $headerValue) {
            if (\strtolower($headerName) === $loweredName) {
                return $headerValue;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function body(array $options = []): mixed
    {
        if (isset($this->body)) {
            return $this->body;
        }

        $this->body = \json_decode($this->response, true, $options['json']['depth'] ?? 512);

        if (\json_last_error() === JSON_ERROR_NONE) {
            return $this->body;
        }

        return $this->response;
    }
}
