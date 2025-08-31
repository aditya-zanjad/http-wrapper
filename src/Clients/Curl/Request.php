<?php

declare(strict_types=1);

namespace AdityaZanjad\Http\Clients\Curl;

use CURLFile;
use Exception;
use Throwable;
use CURLStringFile;
use AdityaZanjad\Http\Enums\Method;
use AdityaZanjad\Http\Interfaces\HttpRequest;

use function AdityaZanjad\Http\Utils\arr_first_fn;

/**
 * @version 1.0
 */
class Request implements HttpRequest
{
    /**
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
        return array_replace(
            $this->makeUrl(),
            $this->makeBody(),
            $this->makeMethod(),
            $this->makeHeaders(),
            $this->makeTimeout(),
            $this->makeSslOptions(),
            $this->makeOtherNecessaryOptions(),
        );
    }

    /**
     * Set HTTP request URL & optionally, its query parameters.
     *
     * @return array<int, string>
     */
    protected function makeUrl(): array
    {
        $url = rtrim(trim($this->data['url']), '/?');

        if (!isset($this->data['query']['params'])) {
            return [CURLOPT_URL => $url];
        }

        $query = http_build_query(
            $this->data['query']['params'],
            $this->data['query']['numeric_prefix']  ??  '',
            $this->data['query']['args_separator']  ??  null,
            $this->data['query']['encoding_type']   ??  PHP_QUERY_RFC3986,
        );

        return [CURLOPT_URL => "{$url}?{$query}"];
    }

    /**
     * Set HTTP request method.
     *
     * @return array
     */
    protected function makeMethod(): array
    {
        return $this->data['method'] !== Method::HEAD
            ? [CURLOPT_CUSTOMREQUEST => $this->data['method']]
            : [CURLOPT_NOBODY => true];
    }

    /**
     * Set HTTP request headers.
     *
     * @return array
     */
    protected function makeHeaders(): array
    {
        $headers        =   [];
        $givenHeaders   =   $this->data['headers'] ?? [];

        foreach ($givenHeaders as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }

        return [
            CURLOPT_HEADER      =>  true,
            CURLOPT_HTTPHEADER  =>  $headers
        ];
    }

    /**
     * Set the SSL verifier to either true OR false.
     *
     * @return array<int, int>
     */
    protected function makeSslOptions(): array
    {
        $ssl = match ($this->data['ssl'] ?? null) {
            false   =>  0,
            true    =>  2,
            default =>  2
        };

        return [
            CURLOPT_SSL_VERIFYHOST  =>  $ssl,
            CURLOPT_SSL_VERIFYPEER  =>  $ssl
        ];
    }

    /**
     * Set the HTTP request timeout.
     *
     * @return array<int, int>
     */
    protected function makeTimeout(): array
    {
        return [CURLOPT_TIMEOUT => $this->data['timeout'] ?? 120];
    }

    /**
     * Set the HTTP request body as per the provider's requirements.
     *
     * @return mixed
     */
    protected function makeBody()
    {
        $body = [
            CURLOPT_POSTFIELDS => null
        ];

        if (!isset($this->data['body']['data']) || empty($this->data['body']['data'])) {
            return $body;
        }

        $contentType = arr_first_fn($this->data['headers'], fn($value, $name) => \strtolower($name) === 'content-type');

        if (\is_null($contentType)) {
            throw new Exception("[Developer][Exception]: You need provide the HTTP header \"Content-Type\" to be able to submit the HTTP form data.");
        }

        switch ($contentType) {
            case 'application/json':
                $body[CURLOPT_POSTFIELDS] = $this->makeJsonFormData();
                break;

            case 'application/x-www-form-urlencoded':
                $body[CURLOPT_POSTFIELDS] = $this->makeUrlEncodedFormData();
                break;

            case 'multipart/form-data':
                $body[CURLOPT_POSTFIELDS] = $this->makeMultipartFormData();
                break;

            case 'text/plain':
            case 'application/xml':
                // Add logic
                break;

            default:
                if (\is_string($this->data['body']) || \is_resource($this->data['body'])) {
                    $body[CURLOPT_POSTFIELDS] = $this->makeMultipartDataFromStringOrResource([
                        'field' => $this->data['body']['field'],
                        'value' => $this->data['body']['data'],
                    ]);

                    break;
                }

                $body[CURLOPT_POSTFIELDS] = $this->data['body']['data'];
                break;
        }

        return $body;
    }

    /**
     * Make the 'application/json' request body as per the HTTP client's requirements.
     *
     * @return string
     */
    protected function makeJsonFormData(): string
    {
        return \json_encode(
            $this->makeRegularFormData(),
            $this->data['body']['options']['json']['flags'] ?? 0,
            $this->data['body']['options']['json']['depth'] ?? 512
        );
    }

    /**
     * Make the 'application/x-www-form-urlencoded' request body as per the HTTP client's requirements.
     *
     * @return string
     */
    protected function makeUrlEncodedFormData(): string
    {
        return \http_build_query(
            $this->makeRegularFormData(),
            $this->data['body']['options']['query']['numeric_prefix'] ?? '',
            $this->data['body']['options']['query']['args_separator'] ?? null,
            $this->data['body']['options']['query']['encoding_type'] ?? PHP_QUERY_RFC1738
        );
    }

    /**
     * Make an array structure of the form data for 'application/json' & 'application/x-www-form-urlencoded' Content types.
     *
     * @return array<string, mixed>
     */
    protected function makeRegularFormData(): array
    {
        $payload = [];

        foreach ($this->data['body']['data'] as $data) {
            $payload[$data['field']] = $data['value'];
        }

        return $payload;
    }

    /**
     * Make the 'multipart/form-data' request body as per the HTTP client's requirements.
     *
     * @return array<string, mixed>
     */
    protected function makeMultipartFormData(): array
    {
        $payload = [];

        foreach ($this->data['body']['data'] as $data) {
            $payload[$data['field']] = match (\gettype($data['value'])) {
                'string', 'resource'    =>  $this->makeMultipartDataFromStringOrResource($data),
                'array'                 =>  $this->makeMultipartFormDataFromArray($data),
                'object'                =>  \fopen($data['value']->getPathname(), 'r'),
                default                 =>  $data['value']
            };
        }

        return $payload;
    }

    protected function makeMultipartDataFromStringOrResource(array $givenData): mixed
    {
        if (\is_resource($givenData['value'])) {
            return $givenData['value'];
        }

        if (!\is_file($givenData['value'])) {
            return $givenData['value'];
        }

        $mime = \extension_loaded('SPL') 
            ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $givenData['value'])
            : \mime_content_type($givenData['value']);

        return new CURLFile($givenData['value'], $mime, $givenData['name'] ?? null);
    }

    protected function makeMultipartFormDataFromArray(array $givenData): mixed
    {
        if (isset($givenData['value']['error']) && $givenData['value']['error'] === UPLOAD_ERR_OK && isset($givenData['value']['tmp_name']) && is_uploaded_file($givenData['value']['tmp_name'])) {
            return $this->makeMultipartDataFromStringOrResource([
                'field' =>  $givenData['field'],
                'value' =>  $givenData['value'],
                'name'  =>  $givenData['name'] ?? null
            ]);
        }

        return \json_encode($givenData['value'], $data['json']['depth'] ?? 512);
    }


    /**
     * Set other necessary options for making a HTTP request.
     *
     * @return array<int, mixed>
     */
    protected function makeOtherNecessaryOptions(): array
    {
        return [
            CURLOPT_MAXREDIRS       =>  $this->data['max_redirects'] ?? 5,
            CURLOPT_HTTP_VERSION    =>  $this->data['version'] ?? CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER  =>  true,
            CURLOPT_FOLLOWLOCATION  =>  true,
        ];
    }
}
