<?php

namespace AdityaZanjad\Http\Clients;

use Exception;
use CurlHandle;
use AdityaZanjad\Http\Enums\ReasonPhrase;
use AdityaZanjad\Http\Builders\CurlRequest;
use AdityaZanjad\Http\Interfaces\HttpClient;

class Curl implements HttpClient
{
    /**
     * @var \CurlHandle $client
     */
    protected CurlHandle $client;

    /**
     * @var array<string, mixed> $res
     */
    protected array $res = [
        'code'              =>  0,      // HTTP status code
        'status'            =>  '',     // HTTP reason phrase
        'headers'           =>  [],
        'body'              =>  [],
        'is_body_decoded'   =>  false,  // Indicates whether the response body is decoded OR not.
    ];

    /**
     * Inject HTTP request data into the class.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(protected array $data)
    {
        if (!extension_loaded('curl')) {
            throw new Exception("[Developer][Exception]: The PHP extension [ext-php] is required to make HTTP requests using [PHP CURL].");
        }

        $this->data                         =   (new CurlRequest($this->data))->build();
        $this->data[CURLOPT_HEADERFUNCTION] =   [$this, 'setHeaderFunction'];
    }

    /**
     * @inheritDoc
     */
    public function send(): static
    {
        $this->client = curl_init();
        curl_setopt_array($this->client, $this->data);
        $this->res['body'] = curl_exec($this->client);
        $this->res['body'] = substr($this->res['body'], curl_getinfo($this->client, CURLINFO_HEADER_SIZE));
        curl_close($this->client);

        // Set HTTP status code & reason phrase.
        $this->res['code']      =   curl_getinfo($this->client, CURLINFO_HTTP_CODE);
        $this->res['status']    =   ReasonPhrase::tryFrom($this->res['code'])?->name;
        $this->res['status']    =   str_replace('_', ' ', $this->res['status']);

        return $this;
    }

    /**
     * Send the HTTP request & obtain its response.
     *
     * @link    https://stackoverflow.com/questions/9183178/can-php-curl-retrieve-response-headers-and-body-in-a-single-request#answer-41135574
     * @link    https://stackoverflow.com/questions/9183178/can-php-curl-retrieve-response-headers-and-body-in-a-single-request#answer-25118032
     *
     * @param   \CurlHandle $client
     * @param   string      $headerLine
     *
     * @return  void
     */
    protected function setHeaderFunction(CurlHandle $client, string $headerLine): int
    {
        $header         =   explode(':', $headerLine, 2);
        $headerLength   =   strlen($headerLine);

        if (count($header) < 2) {
            return $headerLength;
        }

        $header[0]  =   strtolower(trim($header[0]));
        $header[1]  =   trim($header[1]);

        $this->res['headers'][$header[0]] = $header[1];
        return $headerLength;
    }

    /**
     * @inheritDoc
     */
    public function status(): null|string
    {
        return $this->res['status'];
    }

    /**
     * @inheritDoc
     */
    public function code(): int
    {
        return $this->res['code'];
    }

    /**
     * @inheritDoc
     */
    public function body(): mixed
    {
        if ($this->res['is_body_decoded']) {
            return $this->res['body'];
        }

        if (json_validate($this->res['body'])) {
            $this->res['body'] = json_decode($this->res['body']);
        }

        $this->res['is_body_decoded'] = true;
        return $this->res['body'];
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

        return $this->res['headers'][strtolower($key)];
    }
}
