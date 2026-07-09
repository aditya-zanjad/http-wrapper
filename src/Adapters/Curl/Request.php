<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Adapters\Curl;

use finfo;
use CURLFile;
use Exception;
use AdityaZanjad\HttpAdapters\Interfaces\HttpRequest;

/**
 * @version 1.0
 */
class Request implements HttpRequest
{
    public function __construct(protected array $data)
    {
        //
    }

    public function build(): array
    {
        $options = [
            CURLOPT_URL             =>  $this->data['url'],
            CURLOPT_HEADER          =>  false,
            CURLOPT_TIMEOUT         =>  $this->data['timeout'] ?? 30,
            CURLOPT_MAXREDIRS       =>  $this->data['redirects']['max'] ?? 5,
            CURLOPT_FRESH_CONNECT   =>  $this->data['fresh'] ?? false, 
            CURLOPT_FOLLOWLOCATION  =>  $this->data['redirects']['allow'] ?? true,
            CURLOPT_RETURNTRANSFER  =>  true,
        ];

        if (isset($this->data['method'])) {
            $options[CURLOPT_CUSTOMREQUEST] = $this->data['method'];
        }

        if (isset($this->data['query']['params'])) {
            $options[CURLOPT_URL] .= "?{$this->makeQueryParams()}";
        }

        if (isset($this->data['headers'])) {
            $options[CURLOPT_HTTPHEADER] = $this->makeHeaders();
        }

        if (isset($this->data['body']['content'])) {
            $options = \array_replace($options, $this->makeBody());
        }

        if (isset($this->data['ssl']['server']['verify'])) {
            $options = \array_replace($options, $this->makeSslServerVerifyOptions());
        }

        if (isset($this->data['ssl']['client']['certificate'])) {
            $options = \array_replace($options, $this->makeSslClientVerifyOptions());
        }

        if (isset($this->data['proxy'])) {
            $options = \array_replace($options, $this->makeProxyOptions());
        }

        $options = \array_replace($options, $this->makeProgressCallbackOptions());

        if (isset($this->data['read_timeout'])) {
            $options[CURLOPT_LOW_SPEED_LIMIT]   =   1;
            $options[CURLOPT_LOW_SPEED_TIME]    =   $this->data['read_timeout'];
        }

        if (isset($this->data['cookies']['input'])) {
            $options[CURLOPT_COOKIEFILE] = $this->data['cookies']['input'];
        }

        if (isset($this->data['cookies']['output'])) {
            $options[CURLOPT_COOKIEJAR] = $this->data['cookies']['output'];
        }

        if (isset($this->data['ip_resolve'])) {
            $options[CURLOPT_IPRESOLVE] = $this->data['ip_resolve'] === 'v6' ? CURL_IPRESOLVE_V6 : CURL_IPRESOLVE_V4;
        }

        $options[CURLOPT_ENCODING] = $this->makeHeaderForAcceptEncodingField();

        if (isset($this->data['save_to'])) {
            $options[CURLOPT_FILE]            =   \is_string($this->data['save_to']) ? \fopen($this->data['save_to'], 'w+') : $this->data['save_to'];
            $options[CURLOPT_RETURNTRANSFER]  =   false;
        }

        return $options;
    }

    protected function makeQueryParams(): string
    {
        return \http_build_query(
            data: $this->data['query']['params'],
            arg_separator: $this->data['query']['separator'] ?? null,
            encoding_type: $this->data['query']['encoding'] ?? PHP_QUERY_RFC1738,
            numeric_prefix: $this->data['query']['prefix'] ?? '',
        );
    }

    protected function makeHeaders(): array
    {
        $headers = [];

        foreach ($this->data['headers'] as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }

        return $headers;
    }

    protected function makeBody(): array
    {
        $body = [];

        switch ($this->data['body']['type']) {
            case 'json':
                $body[CURLOPT_POSTFIELDS] = $this->makeJsonBody();
                break;

            case 'params':
                $body[CURLOPT_POSTFIELDS] = $this->makeUrlEncodedFormBody();
                break;

            case 'multipart':
                $body[CURLOPT_POSTFIELDS] = $this->makeMultipartFormBody();
                break;

            case 'upload':
                $file = $this->makeUploadBody($this->data['body']['content']);

                $body = [
                    CURLOPT_INFILE      =>  $file['handle'],
                    CURLOPT_INFILESIZE  =>  $file['size']
                ];
                break;
            
            default:
                $body[CURLOPT_POSTFIELDS] = $this->data['body']['content'];
                break;
        }

        return $body;
    }

    protected function makeUploadBody(array $body)
    {
        if (\is_string($body['content'])) {
            if (!\is_file($body['content']) || !\is_readable($body['content'])) {
                throw new Exception("[Developer][Exception]: The request body must contain a readable file.");
            }

            $body['content'] = \fopen($body['content'], 'r');
        }

        $metadata = \stream_get_meta_data($body['content']);

        if ($metadata["wrapper_type"] !== "plainfile") {
            throw new Exception("[Developer][Exception]: The request body contains an invalid file.");
        }

        return [
            'mime'      =>  (new finfo(FILEINFO_MIME_TYPE))->file($metadata['uri']),
            'path'      =>  $metadata['uri'],
            'size'      =>  \filesize($metadata['uri']),
            'handle'    =>  \fopen($body['content'], 'r'),
        ];
    }

    protected function makeJsonBody(): string
    {
        return \json_encode(
            value: $this->data['body']['content'], 
            flags: $this->data['body']['flags'] ?? 0,
            depth: $this->data['body']['depth'] ?? 512
        );
    }

    protected function makeUrlEncodedFormBody(): string
    {
        return \http_build_query(
            data: $this->data['body']['content'],
            arg_separator: $this->data['body']['separator'] ?? null,
            encoding_type: $this->data['body']['encoding'] ?? PHP_QUERY_RFC1738,
            numeric_prefix: $this->data['body']['prefix'] ?? '',
        );
    }

    protected function makeMultipartFormBody(): array
    {
        $body = [];

        foreach ($this->data['body']['content'] as $field) {
            switch (\gettype($field['value'])) {
                case 'array':
                    $value  =   $this->makeMultipartFieldFromArray($field);
                    $body   =   array_merge($body, \is_string($value) ? [$field['name'] => $value] : ["{$field['name']}[]" => $value]);
                    break;

                case 'resource':
                    $body[$field['name']] = $this->makeMultipartFieldFromResource($field);
                    break;

                case 'string':
                default:
                    $body[$field['name']] = $this->makeMultipartFieldFromString($field);
                    break;
            }
        }

        return $body;
    }

    protected function makeMultipartFieldFromArray(array $field): string|array
    {
        if (!isset($field['type']) || $field['type'] !== 'json') {
            return $field['value'];
        }

        return \json_encode(
            value: $field['value'],
            flags: $field['flags'] ?? 0,
            depth: $field['depth'] ?? 512
        );
    }

    protected function makeMultipartFieldFromResource(array $field): CURLFile
    {
        if (\get_resource_type($field['value']) !== 'stream') {
            throw new Exception("[Developer][Exception]: The request body contains invalid file for the field [{$field['name']}].");
        }

        $metadata = \stream_get_meta_data($field['value']);

        if ($metadata["wrapper_type"] !== "plainfile") {
            throw new Exception("[Developer][Exception]: The request body contains invalid file for the field [{$field['label']}].");
        }

        return new CURLFile(
            filename: $metadata['uri'],
            mime_type: $field['mime'] ?? null,
            posted_filename: $field['filename'] ?? $field['name']
        );
    }

    protected function makeMultipartFieldFromString(array $field): string|CURLFile
    {
        if (!isset($field['type']) || $field['type'] !== 'file') {
            return $field['value'];
        }

        if (!\is_file($field['value']) || !\is_readable($field['value'])) {
            throw new Exception("[Developer][Exception]: The request body contains invalid file for the field [{$field['name']}].");
        }

        $field['value'] = \fopen($field['value'], 'r');
        return $this->makeMultipartFieldFromResource($field);
    }

    protected function makeHttpAuthInfo(): array
    {
        return match ($this->data['auth']['type']) {
            'basic'     =>  [CURLOPT_HTTPAUTH => CURLAUTH_BASIC, CURLOPT_USERPWD => "{$this->data['auth']['username']}:{$this->data['auth']['password']}"],
            'digest'    =>  [CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_USERPWD => "{$this->data['auth']['username']}:{$this->data['auth']['password']}"],

            default => throw new Exception("[Developer][Exception]: The HTTP authentication type {$this->data['auth']['type']} is invalid.")
        };
    }

    protected function makeSslClientVerifyOptions(): array
    {
        $options                    =   [];
        $options[CURLOPT_SSLCERT]   =   $this->data['ssl']['client']['certificate'];
            
        if (isset($this->data['ssl']['client']['password'])) {
            $options[CURLOPT_SSLCERTPASSWD] = $this->data['ssl']['client']['password'];
        }

        if (isset($this->data['ssl']['client']['key'])) {
            $options[CURLOPT_SSLKEY] = $this->data['ssl']['client']['key'];
        }

        return $options;
    }

    protected function makeSslServerVerifyOptions(): array
    {
        $options = [];

        if ($this->data['ssl']['server']['verify'] === false) {
            $options[CURLOPT_SSL_VERIFYHOST]    =   0;
            $options[CURLOPT_SSL_VERIFYPEER]    =   false;

            return $options;
        }

        if (!isset($this->data['ssl']['server']['certificate'])) {
            return $options;
        }
        
        if (\is_file($this->data['ssl']['server']['certificate'])) {
            $options[CURLOPT_CAINFO] = $this->data['ssl']['server']['certificate'];
            return $options;
        }

        if (\is_dir($this->data['ssl']['server']['certificate'])) {
            $options[CURLOPT_CAPATH] = $this->data['ssl']['server']['certificate'];
        }

        return $options;
    }

    protected function makeProxyOptions(): array
    {
        $options = [
            CURLOPT_PROXY           =>  $this->data['proxy']['url'],
            CURLOPT_HTTPPROXYTUNNEL =>  $this->data['proxy']['tunnel'] ?? false,

            CURLOPT_PROXYTYPE => match ($this->data['proxy']['type'] ?? 'http') {
                'http'      =>  CURLPROXY_HTTP,
                'socks5'    =>  CURLPROXY_SOCKS5,

                default => throw new Exception("[Developer][Exception]: The parameter [proxy.type] has an invalid value. Valid values are: [http, socks5]")
            }
        ];

        if (!isset($this->data['proxy']['auth'])) {
            return $options;
        }

        $options[CURLOPT_PROXYAUTH] = match ($this->data['proxy']['auth']['type'] ?? 'basic') {
            'basic' =>  CURLAUTH_BASIC,
            'ntlm'  =>  CURLAUTH_NTLM,

            default => throw new Exception("[Developer][Exception]: The parameter [proxy.auth.type] has an invalid value. Valid values are: [basic, ntlm]")
        };

        $options[CURLOPT_PROXYUSERPWD] = "{$this->data['proxy']['auth']['username']}:{$this->data['proxy']['auth']['password']}";
        return $options;
    }

    protected function makeProgressCallbackOptions(): array
    {
        if (!isset($this->data['progress'])) {
            return [
                CURLOPT_NOPROGRESS => true
            ];
        }

        if (!\is_callable($this->data['progress']['callback'])) {
            throw new Exception("[Developer][Exception]: The parameter [progress.callback] must be a valid PHP callback function.");
        }

        return [
            CURLOPT_NOPROGRESS          =>  false,
            CURLOPT_PROGRESSFUNCTION    =>  $this->data['porgress']['callback']
        ];
    }

    protected function makeHeaderForAcceptEncodingField(): string
    {
        if (!isset($this->data['decode'])) {
            return '';
        }

        if (!\is_string($this->data['decode'])) {
            throw new Exception("[Developer][Exception]: The parameter 'decode' has an invalid value. It should be either true (bool), false (bool) or a string");
        }

        return match ($this->data['decode']) {
            'all'   =>  '',
            'none'  =>  'identity',
            default =>  $this->data['decode']
        };
    }
}
