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
     * @var \GuzzleHttp\Psr7\Request
     */
    protected Request $request;

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
     * @return \GuzzleHttp\Psr7\Request
     */
    public function build(): Request
    {
        $this->setQuery();
        $this->setBody();

        return new Request(
            $this->data['method'],
            $this->data['url'],
            $this->data['headers'],
            $this->data['body']
        );
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

        $this->data['url'] .= '?' . http_build_query($this->data['query']);
    }

    /**
     * Set the HTTP request body as per the provider's requirements.
     *
     * @return never
     */
    public function setBody(): never
    {
        $this->data['body'] = match ($this->data['headers']['Content-Type']) {
            'application/json'                  =>  $this->makeJsonPayload(),
            'application/x-www-form-urlencoded' =>  $this->makeUrlEncodedFormPayload(),
            'multipart/form-data'               =>  $this->makeMultipartFormPayload(),
            default                             =>  throw new Exception("[Developer][Exception]: The header 'Content-Type' is set to an invalid value.")
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
