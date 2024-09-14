<?php

namespace AdityaZanjad\Http\Builders;

use Exception;
use CurlHandle;
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
     * Inject necessary data into the class.
     *
     * @param   \CurlHandle           $req
     * @param   array<string, mixed>  $data
     */
    public function __construct(protected CurlHandle $req, protected array $data)
    {
        $this->req = $req;
    }

    /**
     * Build the HTTP request object.
     *
     * @return \CurlHandle
     */
    public function build(): CurlHandle
    {
        $this->setUrl();
        $this->setMethod();
        $this->setHeaders();
        $this->setBody();
        $this->setOtherOptions();

        return $this->req;
    }

    /**
     * Set HTTP request method.
     *
     * @return void
     */
    public function setMethod(): void
    {
        if ($this->data['method'] === 'HEAD') {
            curl_setopt($this->req, CURLOPT_NOBODY, true);
        }

        curl_setopt($this->req, CURLOPT_CUSTOMREQUEST, $this->data['method']);
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
            $url = "{$this->data['url']}?" . http_build_query($this->data['query']);
        }

        curl_setopt($this->req, CURLOPT_URL, $url);
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

        curl_setopt($this->req, CURLOPT_HEADER, true);
        curl_setopt($this->req, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set the HTTP request body as per the provider's requirements.
     *
     * @return void
     */
    public function setBody(): void
    {
        switch ($this->data['headers']['Content-Type']) {
            case 'application/json':
                $this->makeJsonPayload();
                break;

            case 'application/x-www-form-urlencoded':
                $this->makeUrlEncodedFormPayload();
                break;

            case 'multipart/form-data':
                $this->makeMultipartFormPayload();
                break;

            default:
                throw new Exception("[Developer][Exception]: The header 'Content-Type' is set to an invalid value.");
                break;
        }
    }

    /**
     * Make the 'application/json' request body as per the HTTP client's requirements.
     *
     * @return void
     */
    protected function makeJsonPayload(): void
    {
        $payload = [];

        foreach ($this->data['body'] as $data) {
            $payload[$data['name']] = $data['value'];
        }

        curl_setopt($this->req, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    /**
     * Make the 'application/x-www-form-urlencoded' request body as per the HTTP client's requirements.
     *
     * @return void
     */
    protected function makeUrlEncodedFormPayload(): void
    {
        $payload = [];

        foreach ($this->data['body'] as $data) {
            $payload[$data['name']] = $data['value'];
        }

        curl_setopt($this->req, CURLOPT_POSTFIELDS, http_build_query($payload));
    }

    /**
     * Make the 'multipart/form-data' request body as per the HTTP client's requirements.
     *
     * @return void
     */
    protected function makeMultipartFormPayload(): void
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

        curl_setopt($this->req, CURLOPT_POSTFIELDS, $payload);
    }

    /**
     * Set other necessary options for making a HTTP request.
     *
     * @return void
     */
    protected function setOtherOptions(): void
    {
        curl_setopt($this->req, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->req, CURLOPT_TIMEOUT, $this->data['timeout'] ?? 60);
        curl_setopt($this->req, CURLOPT_HTTP_VERSION, $this->data['version'] ?? CURL_HTTP_VERSION_1_1);
        curl_setopt($this->req, CURLOPT_MAXREDIRS, $this->data['redirects']['max'] ?? 10);
    }
}
