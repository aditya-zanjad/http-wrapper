<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Adapters\Curl;

use finfo;
use CURLFile;
use Exception;
use AdityaZanjad\HttpAdapters\Interfaces\HttpRequest;
use AdityaZanjad\HttpAdapters\Enums\ValidQueryEncoding;

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

        if (isset($this->data['query'])) {
            $options[CURLOPT_URL] .= "?{$this->makeQueryParams()}";
        }

        if (isset($this->data['headers'])) {
            $options[CURLOPT_HTTPHEADER] = $this->makeHeaders();
        }

        $requestBodyIsProvided = isset($this->data['body'])
            || isset($this->data['body.form'])
            || isset($this->data['body.json'])
            || isset($this->data['body.multipart']);

        if ($requestBodyIsProvided) {
            $options += $this->makeBody();
        }

        if (isset($this->data['ssl.server.verify'])) {
            $options += $this->makeSslServerVerifyOptions();
        }

        if (isset($this->data['ssl.client.certificate'])) {
            $options += $this->makeSslClientVerifyOptions();
        }

        if (isset($this->data['proxy'])) {
            $options += $this->makeProxyOptions();
        }

        if (isset($this->data['progress_fn'])) {
            $options += [
                CURLOPT_NOPROGRESS          =>  false,
                CURLOPT_PROGRESSFUNCTION    =>  $this->data['progress_fn']
            ];
        }

        if (isset($this->data['low.speed.limit'])) {
            $options[CURLOPT_LOW_SPEED_LIMIT] = $this->data['low.speed.limit'];
        }

        if (isset($this->data['low.speed.timeout'])) {
            $options[CURLOPT_LOW_SPEED_TIME] = $this->data['low.speed.timeout'];
        }

        if (isset($this->data['cookies.input'])) {
            $options[CURLOPT_COOKIEFILE] = $this->data['cookies.input'];
        }

        if (isset($this->data['cookies.output'])) {
            $options[CURLOPT_COOKIEJAR] = $this->data['cookies.output'];
        }

        if (isset($this->data['resolve_ip'])) {
            $options[CURLOPT_IPRESOLVE] = $this->data['resolve_ip'] === 'v6' ? CURL_IPRESOLVE_V6 : CURL_IPRESOLVE_V4;
        }

        $options[CURLOPT_ENCODING] = $this->makeAcceptEncodingHeader();

        if (isset($this->data['save_to'])) {
            $options[CURLOPT_FILE]            =   \is_string($this->data['save_to']) ? \fopen($this->data['save_to'], 'w+') : $this->data['save_to'];
            $options[CURLOPT_RETURNTRANSFER]  =   false;
        }

        return $options;
    }

    protected function makeQueryParams(): string
    {
        $prefix     =   $this->data['query.prefix'] ?? '';
        $separator  =   $this->data['query.separator'] ?? null;

        $encoding = match ($this->data['query.encoding'] ?? null) {
            ValidQueryEncoding::RFC_3986->value =>  PHP_QUERY_RFC3986,
            default                             =>  PHP_QUERY_RFC1738,
        };

        return \http_build_query($this->data['query'], $prefix, $separator, $encoding);
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
        switch (true) {
            case isset($this->data['body.json']):
                return [CURLOPT_POSTFIELDS => $this->makeJsonBody()];

            case isset($this->data['body.form']):
                return [CURLOPT_POSTFIELDS => $this->makeUrlEncodedFormBody()];

            case isset($this->data['body.multipart']):
                return [CURLOPT_POSTFIELDS => $this->makeMultipartFormBody()];

            case isset($this->data['body.upload']):
                $file = $this->makeUploadBodyFileInfo();

                return [
                    CURLOPT_INFILE      =>  $file['handle'],
                    CURLOPT_INFILESIZE  =>  $file['size']
                ];

            default:
                return [CURLOPT_POSTFIELDS => $this->data['body']];
        }
    }

    protected function makeUploadBodyFileInfo(): array
    {
        if (\is_string($this->data['body'])) {
            $this->data['body'] = \fopen($this->data['body'], 'r');
        }

        $metadata = \stream_get_meta_data($this->data['body']);

        return [
            'mime'      => (new finfo(FILEINFO_MIME_TYPE))->file($metadata['uri']),
            'path'      =>  $metadata['uri'],
            'size'      =>  \filesize($metadata['uri']),
            'handle'    =>  \fopen($this->data['body'], 'r'),
        ];
    }

    protected function makeJsonBody(): string
    {
        $flags = (int) ($this->data['body.json.flags'] ?? 0);
        $depth = (int) ($this->data['body.json.depth'] ?? 512);

        return \json_encode($this->data['body.json'], $flags, $depth);
    }

    protected function makeUrlEncodedFormBody(): string
    {
        $prefix     =   $this->data['body.form.prefix'] ?? '';
        $separator  =   $this->data['body.form.separator'] ?? null;

        $encoding = match ($this->data['body.form.encoding'] ?? null) {
            ValidQueryEncoding::RFC_3986->value =>  PHP_QUERY_RFC3986,
            default                             =>  PHP_QUERY_RFC1738,
        };

        return \http_build_query($this->data['body.form'], $prefix, $separator, $encoding);
    }

    protected function makeMultipartFormBody(): array
    {
        $body = null;

        foreach ($this->data['body.multipart'] as $field) {
            switch (\gettype($field['value'])) {
                case 'array':
                    $body[$field['name']] = $this->makeMultipartFieldFromArray($field);

                    if (isset($field['type']) && $field['type'] === 'json') {
                        $body = \json_encode($body, $field['json.flags'] ?? 0, $field['json.depth'] ?? 512);
                    }
                    break;

                case 'resource':
                    $body[$field['name']] = $this->makeMultipartFieldFromResource($field);
                    break;

                case 'string':
                    $body[$field['name']] = $this->makeMultipartFieldFromString($field);
                    break;

                default:
                    $body[$field['name']] = $field['value'];
                    break;
            }
        }

        return $body;
    }

    protected function makeMultipartFieldFromArray(array $field): string|array
    {
        if (isset($field['type']) && $field['type'] !== 'json') {
            return \json_encode($field['value'], $field['flags'] ?? 0, $field['depth'] ?? 512);
        }

        return $field['value'];
    }

    protected function makeMultipartFieldFromString(array $field): string|CURLFile
    {
        if (isset($field['type']) && $field['type'] === 'file') {
            $field['value'] = \fopen($field['value'], 'r');
            return $this->makeMultipartFieldFromResource($field);
        }
        
        return $field['value'];
    }

    protected function makeMultipartFieldFromResource(array $field): CURLFile
    {
        $metadata = \stream_get_meta_data($field['value']);
        return new CURLFile($metadata['uri'], $field['mime'] ?? null, $field['filename'] ?? $field['name']);
    }

    protected function makeHttpAuthInfo(): array
    {
        return match ($this->data['auth']) {
            'basic'     =>  [CURLOPT_HTTPAUTH => CURLAUTH_BASIC, CURLOPT_USERPWD => "{$this->data['auth.username']}:{$this->data['auth.password']}"],
            'digest'    =>  [CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_USERPWD => "{$this->data['auth.username']}:{$this->data['auth.password']}"],

            default => throw new Exception("[Developer][Exception]: The parameter auth is set to an invalid value.")
        };
    }

    protected function makeSslClientVerifyOptions(): array
    {
        $options                    =   [];
        $options[CURLOPT_SSLCERT]   =   $this->data['ssl.client.certificate'];

        if (isset($this->data['ssl.client.password'])) {
            $options[CURLOPT_SSLCERTPASSWD] = $this->data['ssl.client.password'];
        }

        if (isset($this->data['ssl.client.key'])) {
            $options[CURLOPT_SSLKEY] = $this->data['ssl.client.key'];
        }

        return $options;
    }

    protected function makeSslServerVerifyOptions(): array
    {
        $options = [];

        if ($this->data['ssl']['server.verify'] === false) {
            $options[CURLOPT_SSL_VERIFYHOST]    =   0;
            $options[CURLOPT_SSL_VERIFYPEER]    =   false;

            return $options;
        }

        if (isset($this->data['ssl']['server.certificate'])) {
            if (\is_file($this->data['ssl']['server.certificate'])) {
                $options[CURLOPT_CAINFO] = $this->data['ssl']['server.certificate'];
                return $options;
            }
    
            if (\is_dir($this->data['ssl']['server.certificate'])) {
                $options[CURLOPT_CAPATH] = $this->data['ssl']['server.certificate'];
            }
        }

        return $options;
    }

    protected function makeProxyOptions(): array
    {
        $options = [
            CURLOPT_PROXY           =>  $this->data['proxy.url'],
            CURLOPT_HTTPPROXYTUNNEL =>  $this->data['proxy.tunnel'] ?? false,

            CURLOPT_PROXYTYPE => match ($this->data['proxy.type'] ?? 'http') {
                'http'      =>  CURLPROXY_HTTP,
                'socks5'    =>  CURLPROXY_SOCKS5,

                default => throw new Exception("[Developer][Exception]: The parameter [proxy.type] has an invalid value. Valid values are: [http, socks5]")
            }
        ];

        if (!isset($this->data['proxy.auth'])) {
            return $options;
        }

        $options[CURLOPT_PROXYAUTH] = match ($this->data['proxy.auth'] ?? 'basic') {
            'basic' =>  CURLAUTH_BASIC,
            'ntlm'  =>  CURLAUTH_NTLM,

            default => throw new Exception("[Developer][Exception]: The parameter [proxy.auth] has an invalid value. Valid values are: [basic, ntlm]")
        };

        $options[CURLOPT_PROXYUSERPWD] = "{$this->data['proxy.username']}:{$this->data['proxy.password']}";
        return $options;
    }

    protected function makeAcceptEncodingHeader(): string
    {
        if (!isset($this->data['encoding.accept'])) {
            return '';
        }

        if (!\is_string($this->data['encoding.accept'])) {
            throw new Exception("[Developer][Exception]: The parameter 'encoding.accept' has an invalid value. It should be either true (bool), false (bool) or a string");
        }

        return match ($this->data['encoding.accept']) {
            'all'   =>  '',
            'none'  =>  'identity',
            default =>  $this->data['encoding.accept']
        };
    }
}
