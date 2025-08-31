<?php

declare(strict_types=1);

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
            fn($value, $name) => strtolower($name) === 'content-type'
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
         * 
         * !!! Important Note !!!
         */
        $this->data['headers'] = array_filter(
            $this->data['headers'], 
            fn($value, $name) => \strtolower($name) !== 'content-type', 
            ARRAY_FILTER_USE_BOTH
        );

        return new MultipartStream(
            \array_map(
                fn ($data) => match (\gettype($data['value'])) {
                    'string', 'resource'    =>  $this->makeOtherMultipartDataFromStringOrResource($data),
                    'array'                 =>  $this->makeMultipartDataFromArray($data),
                    'object'                =>  $this->makeOtherMultipartDataFromObject($data),
                    default                 =>  [ 'name' => $data['field'], 'contents' => $data['value'] ]
                }, 
                $this->data['body']['data']
            )
        );
    }

    /**
     * Convert the given array data into the JSON data structure when 'Content-Type' is 'multipart/form-data'.
     *
     * @param array<string, mixed> $givenData
     *
     * @return array<string, string>
     */
    protected function makeMultipartDataFromArray(array $givenData): array
    {
        $data = [
            'name' => $givenData['field'],
        ];

        if (isset($givenData['headers'])) {
            $data['headers'] = $givenData['headers'];
        }

        if (isset($givenData['value']['error']) && $givenData['value']['error'] === UPLOAD_ERR_OK && isset($givenData['value']['tmp_name']) && is_uploaded_file($givenData['value']['tmp_name'])) {
            $data['contents'] = \fopen($givenData['value'], 'r');
            return $data;
        }

        $jsonFlag   =   $givenData['json']['flags'] ?? 0;
        $jsonDepth  =   $givenData['json']['depth'] ?? 1024;

        return [
            'name'      =>  $givenData['field'],
            'contents'  =>  json_encode($givenData['value'], $jsonFlag, $jsonDepth),
            'headers'   =>  ['Content-Type' => 'application/json']
        ];
    }

    /**
     * Convert the given array data into the JSON data structure when 'Content-Type' is 'multipart/form-data'.
     *
     * @param array<string, mixed> $givenData
     *
     * @return array<string, string>
     */
    protected function makeOtherMultipartDataFromStringOrResource(array $givenData): array
    {
        $data = [
            'name' => $givenData['field'],
        ];

        if (isset($givenData['headers'])) {
            $data['headers'] = $givenData['headers'];
        }

        $data['contents'] = match (\gettype($givenData['value'])) {
            'string'    =>  \is_file($givenData['value']) ? \fopen($givenData['value'], 'r') : $givenData['value'],
            'resource'  =>  $givenData['value'],
            'object'    =>  \fopen($givenData['value']->getPathname(), 'r')
        };

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function makeOtherMultipartDataFromObject(array $givenData): array
    {
        $data = [
            'name'      =>  $givenData['field'],
            'contents'  =>  \is_file($givenData['value']) ? \fopen($givenData['value'], 'r') : $givenData['value'],
        ];

        if (isset($givenData['headers'])) {
            $data['headers'] = $givenData['headers'];
        }

        return $data;
    }
}
