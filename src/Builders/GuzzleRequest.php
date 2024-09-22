<?php

namespace AdityaZanjad\Http\Builders;

use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\MultipartStream;

/**
 * This class builds the Guzzle HTTP request object required for making a HTTP request.
 *
 * @version 1.0
 * @author  Aditya Zanjad <adityazanjad474@gmail.com>
 */
class GuzzleRequest
{
    /**
     * The array structure as required by GuzzleHttp Client to make the request.
     *
     * @var array<string, mixed> $req
     */
    protected array $req;

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
        $this->setQuery();
        $this->setBody();

        return $this->req;
    }

    public function setUrl(): void
    {
        $this->req['url'] = $this->data['url'];
    }

    /**
     * Set the HTTP request method.
     *
     * @return void
     */
    public function setMethod(): void
    {
        $this->req['method'] = $this->data['method'];
    }

    /**
     * Set query parameters for the HTTP request.
     *
     * @return void
     */
    public function setQuery(): void
    {
        if (!isset($this->data['query'])) {
            return;
        }

        $this->req['options']['query'] = $this->data['query'];
    }

    /**
     * Set headers for the HTTP request.
     *
     * @return void
     */
    public function setHeaders(): void
    {
        $this->req['options']['headers'] = $this->data['headers'] ?? [];
    }

    /**
     * Set HTTP request timeout.
     * 
     * @return void
     */
    public function setTimeout(): void
    {
        if (!isset($this->data['timeout'])) {
            return;
        }

        $this->req['options']['timeout'] = $this->data['timeout'];        
    }

    /**
     * Set the HTTP request body as per the provider's requirements.
     *
     * @return void
     */
    public function setBody(): void
    {
        $this->data['body'] = match ($this->data['headers']['Content-Type'] ?? '') {
            'application/json'                  =>  $this->makeJsonPayload(),
            'application/x-www-form-urlencoded' =>  $this->makeUrlEncodedFormPayload(),
            'multipart/form-data'               =>  $this->makeMultipartFormPayload(),
            default                             =>  []
        };
    }

    /**
     * Make the 'application/json' request body as per the HTTP client's requirements.
     *
     * @return array<string, mixed>
     */
    protected function makeJsonPayload()
    {
        return ['json' => $this->data['body']];
    }

    /**
     * Make the 'application/x-www-form-urlencoded' request body as per the HTTP client's requirements.
     *
     * @return array<string, mixed>
     */
    protected function makeUrlEncodedFormPayload(): array
    {
        return ['form_params' => $this->data['body']];
    }

    /**
     * Make the 'multipart/form-data' request body as per the HTTP client's requirements.
     *
     * @return \GuzzleHttp\Psr7\MultipartStream
     */
    protected function makeMultipartFormPayload(): MultipartStream
    {
        $payload = [];

        foreach ($this->data['body'] as $key => $data) {
            if (is_array($data)) {
                $payload[] = [
                    'name'      =>  $key,
                    'contents'  =>  json_encode($data, 1024),
                    'headers'   =>  ['Content-Type' => 'application/json']
                ];

                continue;
            }

            if (is_resource($data)) {
                $payload[] = [
                    'name'      =>  $key,
                    'contents'  =>  $data,
                    'headers'   =>  ['Content-Type' => mime_content_type($data)]
                ];
            }

            $payload[] = [
                'name'      =>  $data['name'],
                'contents'  =>  $data['value']
            ];
        }

        unset($this->data['headers']['Content-Type']);
        return new MultipartStream($this->data['body']);
    }
}
