<?php

declare (strict_types=1);

namespace AdityaZanjad\Http\Clients\Guzzle;

use Exception;
use Throwable;
use GuzzleHttp\Psr7\Utils;
use AdityaZanjad\Http\Utils\File;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use AdityaZanjad\Http\Interfaces\HttpRequest;

use function AdityaZanjad\Http\Utils\arr_first_fn;

/**
 * @version 1.0
 */
class Request implements HttpRequest
{
    /**
     * The provided HTTP request information.
     *
     * @var array<string, mixed> $data
     */
    protected array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function build()
    {
        // Set default values if not set already.
        $headers        =   $this->data['headers'] ?? [];
        $httpVersion    =   $this->data['version'] ?? '1.1';

        return new GuzzleRequest(
            $this->data['method'],
            $this->makeUrl(),
            $headers,
            $this->makeBody(),
            $httpVersion
        );
    }

    /**
     * Make the HTTP request URL based on the given HTTP request data.
     *
     * @return string
     */
    protected function makeUrl(): string
    {
        $url = rtrim(trim($this->data['url']), '/?');

        if (!isset($this->data['query']['params'])) {
            return $url;
        }

        $query = http_build_query(
            $this->data['query']['params'],
            $this->data['query']['numeric_prefix']  ??  '',
            $this->data['query']['args_separator']  ??  null,
            $this->data['query']['encoding_type']   ??  PHP_QUERY_RFC3986,
        );

        return "{$this->data['url']}?{$query}";
    }

    /**
     * Make the HTTP request body as required by 'GuzzleHttp'.
     *
     * @return null|array<string, mixed>
     */
    protected function makeBody()
    {
        if (!isset($this->data['body']['data'])) {
            return null;
        }

        if (!isset($this->data['headers'])) {
            throw new Exception("[Developer][Exception]: You must set appropriate HTTP headers to be able to pass HTTP request body.");
        }

        $contentType = arr_first_fn(
            $this->data['headers'], 
            fn ($value, $name) => strtolower($name) === 'content-type'
        );

        if (is_null($contentType)) {
            throw new Exception("[Developer][Exception]: You must set the header \"Content-Type\" to be able to pass the HTTP request body.");
        }

        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                return $this->makeUrlEncodedFormData();

            case 'application/json':
                return $this->makeJsonFormData();

            case 'multipart/form-data':
                return $this->makeMultipartFormData();

            default:
                if (is_string($this->data['body']['data']) && file_exists($this->data['body']['data'])) {
                    return Utils::tryFopen($this->data['body']['data'], 'r');
                }

                return $this->data['body']['data'];
        }

        return null;
    }

    /**
     * Prepare the 'application/json' type form data as required by the 'GuzzleHttp' client.
     *
     * @return string
     */
    protected function makeJsonFormData(): string
    {
        $data = [];

        foreach ($this->data['body']['data'] as $field) {
            $data[$field['field']] = $field['value'];
        }

        return json_encode(
            $data,
            $this->data['body']['options']['json']['flags'] ?? 0,
            $this->data['body']['options']['json']['depth'] ?? 512
        );
    }

    /**
     * Prepare the application/x-www-form-urlencoded' type form data as required by the 'GuzzleHttp' client.
     *
     * @return string
     */
    protected function makeUrlEncodedFormData(): string
    {
        $data = [];

        foreach ($this->data['body']['data'] as $field) {
            $data[$field['field']] = $field['value'];
        }

        return http_build_query(
            $data,
            $this->data['body']['options']['query']['numeric_prefix'] ?? '',
            $this->data['body']['options']['query']['args_separator'] ?? null,
            $this->data['body']['options']['query']['encoding_type'] ?? PHP_QUERY_RFC1738
        );
    }

    /**
     * Prepare the 'multipart/form-data' in the format as required by the HTTP client.
     *
     * @return \GuzzleHttp\Psr7\MultipartStream
     */
    protected function makeMultipartFormData(): MultipartStream
    {
        /**
         * !!! Important Note !!!
         *
         * We need to manually remove the header 'Content-Type', if it was added by the user.
         * We need the 'Guzzle' library to fill this header automatically. If we manually
         * provide it to 'Guzzle', it'll cause some problems later on. If we still wish 
         * to manually provide this header, we'll need to remove this code & then,
         * manually provide the multipart content boundary in the same
         * header as well.
         */
        $this->data['headers'] = array_filter(
            $this->data['headers'],
            fn ($value, $name) => strtolower($name) !== 'content-type'
        );

        return new MultipartStream(
            array_map(function ($field) {
                if (is_array($field['value'])) {
                    return $this->makeMultipartJsonData($field);
                }

                return $this->makeOtherMultipartData($field);
            }, $this->data['body']['data'])
        );
    }

    /**
     * Convert the given array data into the JSON data structure when 'Content-Type' is 'multipart/form-data'.
     *
     * @param array<string, mixed> $field
     *
     * @return array<string, string>
     */
    protected function makeMultipartJsonData(array $field): array
    {
        $jsonFlag   =   $field['json']['flags'] ?? 0;
        $jsonDepth  =   $field['json']['depth'] ?? 1024;

        return [
            'name'      =>  $field['field'],
            'contents'  =>  json_encode($field['value'], $jsonFlag, $jsonDepth),
            'headers'   =>  ['Content-Type' => 'application/json']
        ];
    }

    /**
     * @param array<string, mixed> $field
     *
     * @return array<string, mixed>
     */
    protected function makeOtherMultipartData(array $field): array
    {
        try {
            $file = new File($field['value']);
            $file->open();

            return [
                'name'      =>  $field['name'] ?? $file->name(),
                'contents'  =>  $file->contents(),
                'headers'   =>  [ 'Content-Type' => $file->mime() ],
            ];
        } catch (Throwable $e) {
            // dd($e);
            throw new Exception("[Developer][Exception]: {$e->getMessage()}");
        }
    }
}
