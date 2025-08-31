<?php

namespace AdityaZanjad\Http\Clients\Curl;

use Exception;

/**
 * @version 1.0
 */
class ResponseHeaders
{
    /**
     * @var array<string, string[]> $headers
     */
    protected array $headers;

    /**
     * Extract the headers from the HTTP response.
     *
     * @link    https://stackoverflow.com/questions/9183178/can-php-curl-retrieve-response-headers-and-body-in-a-single-request#41135574
     *
     * @param   string  $headerLine
     * @param   array   &$headers
     *
     * @return  int
     */
    public function process($curl, string $headerLine)
    {
        $header         =   explode(':', $headerLine, 2);
        $headerLength   =   strlen($headerLine);

        if (count($header) < 2) {
            return $headerLength;
        }

        $header[0]                  =   strtolower(trim($header[0]));
        $header[1]                  =   trim($header[1]);
        $this->headers[$header[0]]  =   $header[1];

        return $headerLength;
    }

    /**
     * Get the processed response headers.
     *
     * @return array<int|string, string|array<int, string>>
     */
    public function all(): array
    {
        if (empty($this->headers)) {
            throw new Exception("[Developer][Exception]: The HTTP response headers must be processed first.");
        }

        return $this->headers;
    }
}
