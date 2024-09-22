<?php

namespace AdityaZanjad\Http\Builders;

use Exception;
use CURLStringFile;

/**
 * This class builds the Guzzle HTTP request object required for making a HTTP request.
 *
 * @version 1.0
 * @author  Aditya Zanjad <adityazanjad474@gmail.com>
 */
class CurlRequest
{
    /**
     * To contain the HTTP request data as required in a particular format specific PHP CURL.
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * Inject necessary data into the class.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(protected array $data)
    {
        //
    }

    /**
     * Build the HTTP request object.
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $this->setUrl();
        $this->setMethod();
        $this->setHeaders();
        $this->setTimeout();
        $this->setBody();
        $this->setOtherOptions();
        $this->setSslVerifyOptions();

        return $this->options;
    }

    /**
     * Set HTTP request URL & its query parameters.
     *
     * @return void
     */
    public function setUrl(): void
    {
        $this->data['url'] = rtrim($this->data['url'], '?');

        if (isset($this->data['query'])) {
            $this->data['url'] .= '?' . http_build_query($this->data['query']);
        }

        $this->options[CURLOPT_URL] = trim($this->data['url']);
    }

    /**
     * Set HTTP request method.
     *
     * @return void
     */
    public function setMethod(): void
    {
        if ($this->data['method'] === 'HEAD') {
            $this->options[CURLOPT_NOBODY] = true;
            return;
        }

        $this->options[CURLOPT_CUSTOMREQUEST] = $this->data['method'];
    }

    /**
     * Set the HTTP request headers.
     *
     * @return void
     */
    public function setHeaders(): void
    {
        if (!isset($this->data['headers'])) {
            return;
        }

        $headers = [];

        foreach ($this->data['headers'] as $header => $value) {
            $headers[] = "{$header}:{$value}";
        }

        $this->options[CURLOPT_HEADER]      =   true;
        $this->options[CURLOPT_HTTPHEADER]  =   $headers;
    }

    /**
     * Set the SSL verifier to either true OR false.
     *
     * @return void
     */
    public function setSslVerifyOptions(): void
    {
        $this->data['ssl'] ??= true;

        $this->options[CURLOPT_SSL_VERIFYHOST]  =   $this->data['ssl'];
        $this->options[CURLOPT_SSL_VERIFYPEER]  =   $this->data['ssl'];
    }

    /**
     * Set the HTTP request timeout.
     *
     * @return void
     */
    public function setTimeout(): void
    {
        $this->options[CURLOPT_TIMEOUT] = $this->data['timeout'] ?? 120;
    }

    /**
     * Set the HTTP request body as per the provider's requirements.
     *
     * @return void
     */
    public function setBody(): void
    {
        if (!isset($this->data['headers']['Content-Type'])) {
            return;
        }

        $this->options[CURLOPT_POSTFIELDS] = match ($this->data['headers']['Content-Type']) {
            'application/json'                  =>  $this->makeJsonPayload(),
            'application/x-www-form-urlencoded' =>  $this->makeUrlEncodedFormPayload(),
            'multipart/form-data'               =>  $this->makeMultipartFormPayload(),
            default                             =>  throw new Exception("[Developer][Exception]: The header 'Content-Type' is set to an invalid value.")
        };
    }

    /**
     * Make the 'application/json' request body as per the HTTP client's requirements.
     *
     * @return string
     */
    protected function makeJsonPayload(): string
    {
        $payload = [];

        foreach ($this->data['body'] as $data) {
            $payload[$data['name']] = $data['value'];
        }

        return json_encode($payload);
    }

    /**
     * Make the 'application/x-www-form-urlencoded' request body as per the HTTP client's requirements.
     *
     * @return string
     */
    protected function makeUrlEncodedFormPayload(): string
    {
        $payload = [];

        foreach ($this->data['body'] as $data) {
            $payload[$data['name']] = $data['value'];
        }

        return http_build_query($payload);
    }

    /**
     * Make the 'multipart/form-data' request body as per the HTTP client's requirements.
     *
     * @return array<string, mixed>
     */
    protected function makeMultipartFormPayload(): array
    {
        $payload = [];

        foreach ($this->data['body'] as $data) {
            if (is_array($data)) {
                $payload[$data['name']] = json_encode($data, 1024);
                continue;
            }

            if (is_resource($data)) {
                $payload[$data['name']] = new CURLStringFile(
                    $data['value'],
                    $data['label'] ?? $data['name'],
                    mime_content_type($data['value'])
                );
            }

            $payload[$data['name']] = $data['value'];
        }

        return $payload;
    }

    /**
     * Set other necessary options for making a HTTP request.
     *
     * @return void
     */
    protected function setOtherOptions(): void
    {
        $this->options[CURLOPT_RETURNTRANSFER]  =   true;
        $this->options[CURLOPT_HTTP_VERSION]    =   $this->data['version'] ?? CURL_HTTP_VERSION_1_1;
        $this->options[CURLOPT_MAXREDIRS]       =   $this->data['max_redirects'] ?? 5;
    }
}
