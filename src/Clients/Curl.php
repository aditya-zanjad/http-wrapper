<?php

namespace AdityaZanjad\Http\Clients;

use CurlHandle;
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
        'code'      =>  0,
        'status'    =>  '',
        'headers'   =>  [],
        'body'      =>  []
    ];

    /**
     * Inject HTTP request data into the class.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(protected array $data)
    {
        $this->data     =   (new CurlRequest($this->data))->build();
        $this->client   =   curl_init();
    }

    /**
     * @inheritDoc
     */
    public function send(): static
    {
        $this->data[CURLOPT_HEADERFUNCTION] = [$this, 'setHeaderFunction'];
        curl_setopt_array($this->client, $this->data);
        $this->res['body'] = curl_exec($this->client);
        curl_close($this->client);
        $this->extractResponseData();

        return $this;
    }

    /**
     * Decode and simplify the received HTTP response.
     *
     * @return void
     */
    protected function extractResponseData(): void
    {
        /**
         * This code focuses on extracting the body of the HTTP response.
         * This code is taken from the link below.
         * 
         * @link https://gist.github.com/christeredvartsen/6620626
         */
        $matches = [];
        preg_match('#^HTTP/1.(?:0|1) [\d]{3} (.*)$#m', $this->res['body'], $matches);
        
        $this->res['status']    =   $matches[1];
        $this->res['code']      =   curl_getinfo($this->client, CURLINFO_HTTP_CODE);
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
    public function status(): string
    {
        return $this->res['status'];
    }

    /**
     * @inheritDoc
     */
    public function code(): int
    {
        return curl_getinfo($this->client, CURLINFO_HTTP_CODE);
    }

    /**
     * @inheritDoc
     */
    public function body(): mixed
    {
        if (!empty($this->res['body'])) {
            return $this->res['body'];
        }

        if (json_validate($this->res['body'])) {
            $this->res['body'] = json_decode($this->res['body']);
        }

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
