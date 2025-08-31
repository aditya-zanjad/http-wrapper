<?php

declare (strict_types=1);

namespace AdityaZanjad\Http;

use Exception;
use AdityaZanjad\Http\Enums\Client;
use AdityaZanjad\Http\Enums\Method;
use AdityaZanjad\Http\Interfaces\HttpClient;
use AdityaZanjad\Http\Interfaces\HttpResponse;

use function AdityaZanjad\Validator\Presets\validate;

/**
 * @version 2.0
 */
class Http
{
    /**
     * @var \AdityaZanjad\Http\Interfaces\HttpClient $client
     */
    protected HttpClient $client;

    /**
     * @param string $provider
     */
    public function __construct(string $provider = 'auto')
    {
        $provider       =   Client::valueOf($provider);
        $this->client   =   new $provider();
    }

    /**
     * Send a single HTTP request & obtain its response.
     *
     * @param array<string, mixed> $data
     *
     * @return \AdityaZanjad\Http\Interfaces\HttpResponse
     */
    public function send(array $data): HttpResponse
    {
        $validHttpMethods = Method::join();

        $validator = validate($data, [
            'url'               =>  'required|string|url',
            'method'            =>  "required|string|in:{$validHttpMethods}",
            'headers'           =>  'array|min:1',
            'headers.*'         =>  'required_with:headers|string|min:1',
            'body'              =>  'array|min:1',
            'body.data'         =>  'required_with:body|min:2',
            'body.data.*.field' =>  'required_with:body.data|string|min:1',
            'body.data.*.value' =>  'required_with:body.data|min:1'
        ]);

        if ($validator->failed()) {
            throw new Exception("[Developer][Exception]: {$validator->errors()->first()}");
        }

        return $this->client->send($data);
    }

    /**
     * Send concurrent bulk HTTP requests & obtain their responses.
     *
     * @param array<int|string, mixed> $data
     *
     * @return array<int|string, \AdityaZanjad\Http\Interfaces\HttpResponse>
     */
    public function pool(array $data): array
    {
        $validHttpMethods = Method::join();

        $validator = validate($data, [
            '*'                 =>  'required|array|min:2',
            '*.url'             =>  'required|string|url',
            '*.method'          =>  "required|string|in:{$validHttpMethods}",
            '*.headers'         =>  'array|min:1',
            '*.headers.*'       =>  'required_with:headers|string|min:1',
            '*.body'            =>  'array|min:1',
            '*.body.*'          =>  'required_with:body|min:2',
            '*.body.*.field'    =>  'required_with:body|string|min:1',
            '*.body.*.value'    =>  'required_with:body|min:1'
        ]);

        if ($validator->failed()) {
            throw new Exception("[Developer][Exception]: {$validator->errors()->first()}");
        }

        return $this->client->pool($data);
    }
}
