<?php

namespace AdityaZanjad\Http\Clients\Stream;

use Exception;
use Throwable;
use AdityaZanjad\Http\Interfaces\HttpRequest;

use function AdityaZanjad\Http\Utils\arr_set;
use function AdityaZanjad\Http\Utils\str_random;
use function AdityaZanjad\Http\Utils\arr_first_fn;
use function AdityaZanjad\Http\Utils\str_contains_v2;
use function AdityaZanjad\Http\Utils\arr_get_or_default;

/**
 * @version 1.0
 */
class Request implements HttpRequest
{
    /**
     * The input data based on which we want to create our HTTP request data.
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
    public function build(): array
    {
        $request = [
            'url' => $this->makeUrl(),

            'http' => [
                'method'        =>  $this->data['method'],
                'timeout'       =>  $this->data['timeout'] ?? 120,
                'ignore_errors' =>  true
            ]
        ];

        if (isset($this->data['body'])) {
            $request['http']['content'] = $this->makeBody();
        }

        if (isset($this->data['headers'])) {
            $request['http']['header'] = $this->makeHeaders();
        }

        return $request;
    }

    /**
     * Make the HTTP request URL based on the given URL.
     *
     * @return string
     */
    protected function makeUrl(): string
    {
        if (!isset($this->data['query']['params'])) {
            return $this->data['url'];
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
     * Make the HTTP request headers required to send the HTTP request.
     *
     * @return string
     */
    protected function makeHeaders(): string
    {
        $headers            =   $this->data['headers'] ?? [];
        $transformedHeaders =   '';

        foreach ($headers as $name => $value) {
            $transformedHeaders .= "{$name}: {$value}\r\n";
        }

        return $transformedHeaders;
    }

    /**
     * Make the HTTP request body required to send the HTTP request.
     *
     * @return mixed
     */
    protected function makeBody()
    {
        if (!isset($this->data['body']['data'])) {
            return null;
        }

        if (!isset($this->data['headers'])) {
            throw new Exception("[Developer][Exception]: You must set appropriate HTTP headers to be able to pass HTTP request body.");
        }

        $contentType = arr_first_fn($this->data['headers'], fn ($value, $name) => strtolower($name) === 'content-type');

        if (is_null($contentType)) {
            throw new Exception("[Developer][Exception]: You must set the header \"Content-Type\" to be able to pass the HTTP request body.");
        }

        switch ($contentType) {
            case 'application/json':
                return $this->makeJsonFormData();

            case 'application/x-www-form-urlencoded':
                return $this->makeUrlEncodedFormData();

            case 'multipart/form-data':
                return $this->makeMultipartFormData();

            default:
                // TODO => Add logic for handling a single file upload through the file path.
                // TODO => Add logic for handling a single file upload through the resource.
                return $this->data['body']['data'];
        }
    }

    /**
     * Make the 'application/json' form data.
     *
     * @return string
     */
    protected function makeJsonFormData(): string
    {
        $transformedData = [];

        foreach ($this->data['body']['data'] as $data) {
            $transformedData[$data['name']] = $data['value'];
        }

        return json_encode(
            $transformedData,
            $this->data['body']['options']['json']['flags'] ?? 0,
            $this->data['body']['options']['json']['depth'] ?? 512,
        );
    }

    /**
     * Make the 'application/x-www-form-urlencoded' form data.
     *
     * @return string
     */
    protected function makeUrlEncodedFormData(): string
    {
        $transformedData = [];

        foreach ($this->data['body']['data'] as $data) {
            $transformedData[$data['name']] = $data['value'];
        }

        return http_build_query(
            $transformedData,
            $this->data['body']['options']['query']['numeric_prefix']   ??  '',
            $this->data['body']['options']['query']['args_separator']   ??  null,
            $this->data['body']['options']['query']['encoding_type']    ??  PHP_QUERY_RFC1738,
        );
    }

    /**
     * Make the 'multipart/form-data' form data.
     *
     * @return array<string, mixed>
     */
    protected function makeMultipartFormData(): string
    {
        $this->data['headers'] = array_filter(
            $this->data['headers'],
            fn ($value) => str_contains_v2(strtolower($value), 'multipart/form-data')
        );

        $boundary = arr_get_or_default($this->data, 'body.options.multipart.boundary', str_random(12));
        arr_set($this->data, 'headers.Content-Type', "multipart/form-data; boundary={$boundary}");

        $body = '';

        foreach ($this->data['body']['data'] as $field) {
            if (is_array($field['value'])) {
                $body .= $this->makeMultiPartJsonData($field, $boundary);
                continue;
            }

            if (is_resource($field['value'])) {
                $body .= $this->makeMultipartFileFromResource($field, $boundary);
                continue;
            }

            if (is_string($field['value']) && file_exists($field['value'])) {
                $body .= $this->makeMultipartFileFromPath($field, $boundary);
                continue;
            }

            $body .= $this->makeDefaultMultipartData($field, $boundary);
        }

        return $body;
    }

    protected function makeMultiPartJsonData(array $field, string $boundary): string
    {
        $body = '';

        $body   .=  "{$boundary}\r\n Content-Disposition: form-data; name: \"{$field['label']}\"\r\n\r\n";
        $body   .=  "name: \"{$field['label']}\"\r\n\r\n";
        $body   .=  json_encode($field['value'], $field['json']['flags'] ?? 0, $field['json']['depth'] ?? 512);

        return $body;
    }

    protected function makeMultipartFileFromPath(array $field, string $boundary): string
    {
        if (!is_readable($field['value'])) {
            throw new Exception("[Developer][Exception]: Failed to open the file at the given path [{$field['value']}].");
        }

        $body   =   '';
        $file   =   null;

        if (is_string($file) && file_exists($file)) {
            try {
                $file = fopen($field['value'], 'r');
            } catch (Throwable $e) {
                // dd($e);
                throw new Exception("[Developer][Exception]: Unable to open the file at the given path [{$field['value']}]. [Error: {$e->getMessage()}]");
            }
        }

        if ($file === false) {
            throw new Exception("[Developer][Exception]: Failed to open the file [{$field['value']}] due to unknown reasons.");
        }

        $fileMimeType   =   mime_content_type($file);
        $metadata       =   stream_get_meta_data($file);
        $filename       =   isset($file['name']) ? $file['name'] : basename($metadata['uri']);

        $body   .=  "{$boundary}\r\n Content-Disposition: form-data; name: \"{$filename}\"; filename=\"{$field['value']}\"\r\n";
        $body   .=  "Content-Type: {$fileMimeType}\r\n\r\n";
        $body   .=  "{$file}\r\n";

        return $body;
    }

    protected function makeMultipartFileFromResource(array $field, string $boundary): string
    {
        $body           =   '';
        $streamMetadata =   stream_get_meta_data($field['value']);

        if (!in_array($streamMetadata['wrapper_type'], ['plainfile'])) {
            $body   .=  "{$boundary}\r\n Content-Disposition: form-data; name: \"{$field['label']}\"\r\n\r\n";
            $body   .=  "name: \"{$field['label']}\"\r\n\r\n";
            $body   .=  $field['value'];

            return $body;
        }

        $fileMimeType = mime_content_type($field['value']);

        $body   .=  "{$boundary}\r\n Content-Disposition: form-data; name: \"{$field['label']}\"; filename=\"{$field['value']}\"\r\n";
        $body   .=  "Content-Type: {$fileMimeType}\r\n\r\n";
        $body   .=  "{$field['value']}\r\n";

        return $body;
    }

    protected function makeDefaultMultipartData(array $field, string $boundary): string
    {
        $body = '';

        $body   .=  "{$boundary}\r\n Content-Disposition: form-data; name: \"{$field['label']}\"\r\n\r\n";
        $body   .=  "name: \"{$field['label']}\"\r\n\r\n";
        $body   .=  $field['value'];

        return $body;
    }
}
