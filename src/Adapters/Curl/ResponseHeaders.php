<?php

namespace AdityaZanjad\HttpAdapters\Adapters\Curl;

use Exception;
use CurlHandle;

/**
 * @version 1.0
 */
class ResponseHeaders
{
    /**
     * @var array<string, string[]> $headers
     */
    protected array $headers = [];

    /**
     * Extract the headers from the HTTP response.
     *
     * @link    https://stackoverflow.com/questions/9183178/can-php-curl-retrieve-response-headers-and-body-in-a-single-request#41135574
     *
     * @param   \CurlHandle $curl
     * @param   string      $headerLine
     *
     * @return  int
     */
    public function process(CurlHandle $curl, string $headerLine)
    {
        $header         =   \explode(":", $headerLine, 2);
        $headerLength   =   \strlen($headerLine);

        if (\count($header) < 2) {
            return $headerLength;
        }

        $name                   =   \strtolower(trim($header[0]));
        $value                  =   \trim($header[1]);
        $this->headers[$name]   =   $value;

        return $headerLength;
    }

    /**
     * Get the processed response headers.
     *
     * @return array<int|string, string|array<int, string>>
     */
    public function all(): array
    {
        if (!isset($this->headers)) {
            throw new Exception(
                "[Developer][Exception]: In order to be able to access the HTTP response headers, they must be processed with the [CURLOPT_HEADERFUNCTION] first."
            );
        }

        return $this->headers;
    }
}
