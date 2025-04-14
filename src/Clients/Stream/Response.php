<?php

namespace AdityaZanjad\Http\Clients\Stream;

use Exception;
use App\Wrappers\Base\Core\Utils\Arr;
use AdityaZanjad\Http\Enums\ReasonPhrase;
use AdityaZanjad\Http\Interfaces\HttpResponse;

/**
 * @version 1.0
 */
class Response implements HttpResponse
{
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
     * @var bool $bodyIsProcessed
     */
    protected bool $bodyIsProcessed;

    /**
     * Inject necessary data into the class.
     *
     * @param   array<string, string|array<int, string>>    $headers
     * @param   mixed                                       $stream
     */
    public function __construct(array $headers, $stream)
    {
        if (!is_array($headers) && !is_bool($headers)) {
            throw new Exception("[Developer][Exception]: The provided response headers have an invalid format.");
        }

        $this->code             =   $this->makeStatusCode($headers);
        $this->status           =   ReasonPhrase::keyOf($this->code);
        $this->headers          =   $headers;
        $this->body             =   $stream;
        $this->bodyIsProcessed  =   true;
    }

    /**
     * Obtain the HTTP response's status code.
     *
     * @param array<string, string|array<int, string>> $headers
     *
     * @return int|null
     */
    protected function makeStatusCode(array $headers)
    {
        foreach ($headers as $value) {
            if (preg_match('/^HTTP\/1\.\d\s+(\d+)/', $value, $matches)) {
                return (int) $matches[1];
            }
        }

        return 500;
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
    public function header(string $name): string
    {
        return Arr::get($this->headers, $name);
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
    public function body(array $options = [])
    {
        if ($this->bodyIsProcessed) {
            return $this->body;
        }

        $decoded = json_decode(
            $this->body,
            true,
            $options['json']['depth'] ?? 512,
            $options['json']['flags'] ?? 0
        );

        $this->bodyIsProcessed = true;

        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->body = $decoded;
        }

        return $this->body;
    }
}
