<?php

declare(strict_types=1);

namespace AdityaZanjad\Http\Clients\Curl;

use AdityaZanjad\Http\Enums\ReasonPhrase;
use AdityaZanjad\Http\Interfaces\HttpResponse;

use function AdityaZanjad\Http\Utils\arr_first;
use function AdityaZanjad\Http\Utils\arr_first_fn;

/**
 * @version 1.0
 */
class Response implements HttpResponse
{
    /**
     * @var mixed $curl
     */
    protected mixed $curl;

    /**
     * @var bool|string $response
     */
    protected $response;

    /**
     * @var int $code
     */
    protected int $code;

    /**
     * @var string $status
     */
    protected string $status;

    /**
     * @var array<string, string> $headers
     */
    protected array $headers;

    /**
     * @var mixed $body
     */
    protected $body;

    /**
     * @param   mixed                                           $curl
     * @param   array<int|string, string|array<int, string>>    $headers
     * @param   bool|string                                     $response
     */
    public function __construct($curl, array $headers, $response)
    {
        $this->curl     =   $curl;
        $this->headers  =   $headers;
        $this->response =   $response;
        $this->code     =   curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->status   =   ReasonPhrase::keyOf($this->code);
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
    public function header(string $name)
    {
        $loweredName    =   strtolower($name);
        $header         =   arr_first_fn($this->headers, fn ($value, $header) => strtolower($header) === $loweredName);

        if (is_string($header)) {
            return $header;
        }

        if (count($header) > 1) {
            return $header;
        }

        return arr_first($header);
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

        $body           =   substr($this->response, curl_getinfo($this->curl, CURLINFO_HEADER_SIZE));
        $decodedBody    =   json_decode($body, true, $options['json']['depth'] ?? 512);
        $this->body     =   (json_last_error() === JSON_ERROR_NONE) ? $decodedBody : $body;

        return $this->body;
    }
}
