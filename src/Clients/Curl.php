<?php

namespace AdityaZanjad\Http\Clients;

use CurlHandle;
use AdityaZanjad\Http\Builders\CurlRequest;
use AdityaZanjad\Http\Interfaces\HttpClient;


class Curl implements HttpClient
{
    /**
     * @var \CurlHandle $req
     */
    protected CurlHandle $req;

    /**
     * @var array<string, mixed> $res
     */
    protected array $res;

    /**
     * Inject HTTP request data into the class.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(protected array $data)
    {
        $this->req = (new CurlRequest(curl_init(), $data))->build();
    }

    /**
     * Send the HTTP request & obtain its response.
     * 
     * @link    https://stackoverflow.com/questions/9183178/can-php-curl-retrieve-response-headers-and-body-in-a-single-request#answer-41135574
     * @link    https://stackoverflow.com/questions/9183178/can-php-curl-retrieve-response-headers-and-body-in-a-single-request#answer-25118032
     *
     * @return  void
     */
    protected function sendRequest(): void
    {
        $headers = [];
        
        /**
         * This code is taken from these 'StackOverflow' answers linked below.
         * 
         * @link    https://stackoverflow.com/questions/9183178/can-php-curl-retrieve-response-headers-and-body-in-a-single-request#answer-41135574
         * @link    https://stackoverflow.com/questions/9183178/can-php-curl-retrieve-response-headers-and-body-in-a-single-request#answer-25118032
         */
        curl_setopt(
            $this->req, 
            CURLOPT_HEADERFUNCTION, 
            function (CurlHandle $req, string $headerLine) use (&$headers) {
                $header         =   explode(':', $headerLine, 2);
                $headerLength   =   strlen($headerLine);

                if (count($header) < 2) {
                    return $headerLength;
                }

                $header[0] = strtolower(trim($header[0]));
                $header[1] = trim($header[1]);

                $headers[$header[0]] = $header[1];
                return $headerLength;
            }
        );

        $this->res['response'] = curl_exec($this->req);
        curl_close($this->req);
        $this->res['headers'] = $headers;
    }

    /**
     * @inheritDoc
     */
    public function status(): int
    {
        return curl_getinfo($this->req, CURLINFO_HTTP_CODE);
    }

    /**
     * @link https://gist.github.com/christeredvartsen/6620626
     *
     * @inheritDoc
     */
    public function phrase(): string
    {
        /**
         * This code is taken from the link below.
         * 
         * @link https://gist.github.com/christeredvartsen/6620626
         */
        $matches = [];
        preg_match('#^HTTP/1.(?:0|1) [\d]{3} (.*)$#m', $this->res['response'], $matches);
        return $matches[1];
    }

    /**
     * @inheritDoc
     */
    public function decode(): mixed
    {
        if (json_validate($this->res['response'])) {
            return json_encode($this->res['response']);
        }

        return $this->res['response'];
    }

    /**
     * @inheritDoc
     */
    public function headers(): array
    {
        return $this->res['headers'];

    }

    /**
     * @inheritDoc
     */
    public function header(string $key): mixed
    {
        if (!array_key_exists($key, $this->res['headers'])) {
            return null;
        }

        return $this->res['headers'][$key];
    }
}
