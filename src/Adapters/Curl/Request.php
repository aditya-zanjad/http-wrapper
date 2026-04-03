<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Adapters\Curl;

use Exception;
use AdityaZanjad\HttpAdapters\Interfaces\HttpRequest;

class Request implements HttpRequest
{
    public function __construct(protected array $options)
    {
        //
    }

    public function build(): array
    {
        $options = [
            CURLOPT_URL             =>  $this->options['url'],
            CURLOPT_HEADER          =>  false,
            CURLOPT_TIMEOUT         =>  $this->options['timeout'] ?? 30,
            CURLOPT_MAXREDIRS       =>  $this->options['redirects']['max'] ?? 5,
            CURLOPT_CUSTOMREQUEST   =>  $this->options['method'] ?? 'GET',
            CURLOPT_FRESH_CONNECT   =>  $this->options['fresh'] ?? false, 
            CURLOPT_FOLLOWLOCATION  =>  $this->options['redirects']['allow'] ?? true,
            CURLOPT_RETURNTRANSFER  =>  true,
        ];

        if (isset($this->options['version'])) {
            $options[CURLOPT_HTTP_VERSION] = $this->options['version'];
        }

        if (isset($this->options['read_timeout'])) {
            $options[CURLOPT_LOW_SPEED_LIMIT]   =   1;
            $options[CURLOPT_LOW_SPEED_TIME]    =   $this->options['read_timeout'];
        }

        if (isset($this->requests['query'])) {
            $options[CURLOPT_URL] .= \http_build_query(
                $this->options['query']['params'],
                $this->options['query']['options']['prefix']    ??  '',
                $this->options['query']['options']['separator'] ??  null,
                $this->options['query']['options']['encoding']  ??  PHP_QUERY_RFC1738,
            );
        }

        if (isset($this->options["headers"])) {
            $options[CURLOPT_HTTPHEADER] = $this->makeHeaders();
        }

        if (isset($this->options['auth'])) {
            $options += $this->makeAuthData();
        }

        if (isset($this->options['cookies']['input'])) {
            $options[CURLOPT_COOKIEFILE] = $this->options['cookies']['input'];
        }

        if (isset($this->options['cookies']['output'])) {
            $options[CURLOPT_COOKIEJAR] = $this->options['cookies']['output'];
        }

        if (isset($this->options['ssl']['certificate'])) {
            $options += $this->makeSslOptions();
        }

        $options[CURLOPT_ENCODING] = $this->makeAcceptEncodingHeader();

        if (isset($this->options['progress'])) {
            $options += $this->makeProgressCallable();
        }

        if (isset($this->options['ip_resolve'])) {
            $options[CURLOPT_IPRESOLVE] = $this->options['ip_resolve'] === 'v6' ? CURL_IPRESOLVE_V6 : CURL_IPRESOLVE_V4;
        }

        if (isset($this->options["body"]["content"])) {
            $options += $this->makeBody();
        }

        if (isset($this->options['save_to'])) {
            $this->options[CURLOPT_FILE]            =   \is_string($this->options['save_to']) ? \fopen($this->options['save_to'], 'w+') : $this->options['save_to'];
            $this->options[CURLOPT_RETURNTRANSFER]  =   false;
        }

        return $options;
    }

    protected function makeHeaders(): array
    {
        $headers = [];

        foreach ($this->options['headers'] as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }

        return $headers;
    }

    protected function makeAuthData(): array
    {
        $data = match ($this->options['auth']['method']) {
            'basic'     =>  [CURLOPT_HTTPAUTH => CURLAUTH_BASIC, CURLOPT_USERPWD => "{$this->options['auth']['username']}:{$this->options['auth']['password']}"],
            'digest'    =>  [CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_USERPWD => "{$this->options['auth']['username']}:{$this->options['auth']['password']}"],

            default => throw new Exception("[Developer][Exception]: The HTTP authentication method {$this->options['auth']['method']} is either invalid or not supported yet.")
        };

        return [
            CURLOPT_HTTPAUTH => $data['method']
        ];
    }

    protected function makeSslOptions(): array
    {
        $options                    =   [];
        $options[CURLOPT_SSLCERT]   =   $this->options['ssl']['client']['certificate'];
            
        if (isset($this->options['ssl']['client']['password'])) {
            $options[CURLOPT_SSLCERTPASSWD] = $this->options['ssl']['client']['password'];
        }

        if (isset($this->options['ssl']['client']['key'])) {
            $options[CURLOPT_SSLKEY] = $this->options['ssl']['client']['key'];
        }

        if (isset($this->options['ssl']['verify']['allow']) === false) {
            $options[CURLOPT_SSL_VERIFYPEER]    =   false;
            $options[CURLOPT_SSL_VERIFYHOST]    =   0;

            return $options;
        }

        $options[CURLOPT_SSL_VERIFYPEER]    =   true;
        $options[CURLOPT_SSL_VERIFYHOST]    =   2;

        if (!isset($options['ssl']['verify']['certificate'])) {
            return $options;
        }
            
        
        if (\is_file($options['ssl']['verify']['certificate'])) {
            $options[CURLOPT_CAINFO] = $options['ssl']['verify']['certificate'];
            return $options;
        }

        if (\is_dir($options['ssl']['verify']['certificate'])) {
            $options[CURLOPT_CAINFO] = $options['ssl']['verify']['certificate'];
        }

        return $options;
    }

    protected function makeAcceptEncodingHeader(): string
    {
        if (!isset($this->options['decode'])) {
            return '';
        }

        if (!\is_string($this->options['decode'])) {
            throw new Exception("[Developer][Exception]: The parameter 'decode' has an invalid value. It should be either true (bool), false (bool) or a string");
        }

        return match ($this->options['decode']) {
            'all'   =>  '',
            'none'  =>  'identity',
            default =>  $this->options['decode']
        };
    }

    protected function makeProgressCallable(): array
    {
        if ($this->options['progress']['allow'] === false) {
            return [
                CURLOPT_NOPROGRESS => true
            ];
        }

        if (!\is_callable($this->options['progress']['callback'])) {
            throw new Exception("[Developer][Exception]: The parameter progress.callback must be a valid PHP callback function.");
        }

        return [
            CURLOPT_NOPROGRESS          =>  false,
            CURLOPT_PROGRESSFUNCTION    =>  $this->options['porgress']['callback']
        ];
    }

    protected function makeProxyOptions(): array
    {
        $options = [
            CURLOPT_PROXY           =>  $this->options['proxy']['url'],
            CURLOPT_HTTPPROXYTUNNEL =>  $this->options['proxy']['tunnel'] ?? false,

            CURLOPT_PROXYTYPE => match ($this->options['proxy']['type'] ?? 'http') {
                'http'      =>  CURLPROXY_HTTP,
                'socks5'    =>  CURLPROXY_SOCKS5,

                default => throw new Exception("[Developer][Exception]: The parameter [proxy.type] has an invalid value. Valid values are: [http, socks5]")
            }
        ];

        if (!isset($this->options['proxy']['auth'])) {
            return $options;
        }

        $options[CURLOPT_PROXYAUTH] = match ($this->options['proxy']['auth']['type'] ?? 'basic') {
            'basic' =>  CURLAUTH_BASIC,
            'ntlm'  =>  CURLAUTH_NTLM,

            default => throw new Exception("[Developer][Exception]: The parameter [proxy.auth.type] has an invalid value. Valid values are: [basic, ntlm]")
        };

        $options[CURLOPT_PROXYUSERPWD] = "{$this->options['proxy']['auth']['username']}:{$this->options['proxy']['auth']['password']}";
        return $options;
    }

    protected function makeBody(): mixed
    {
        $contentType = null;

        foreach ($this->options["headers"] as $name => $value) {
            if (\strtolower($name) === "content-type") {
                $contentType = $value;
            }
        }

        if (\is_null($contentType)) {
            throw new Exception("[Developer][Exception]: The header [Content-Type] is required along with the request payload.");
        }

        return [
            CURLOPT_POSTFIELDS => match ($contentType) {
                "application/json"                  =>  $this->makeJsonContent(),
                "multipart/form-data"               =>  $this->makeMultipartFormData(),
                "application/x-www-form-urlencoded" =>  $this->makeUrlEncodedFormContent(),

                default => throw new Exception("[Developer][Exception]: The request payload type [{$contentType}] is either invalid OR not supported by this package yet."),
            }
        ];
    }

    protected function makeJsonContent(): string
    {
        $body = [];

        foreach ($this->options["body"]["content"] as $field) {
            $body[$field["label"]] = $field["value"];
        }

        return \json_encode(value: $body, depth: $this->options["body"]["options"]["depth"] ?? 1024);
    }

    protected function makeUrlEncodedFormContent()
    {
        $body = [];

        foreach ($this->options["body"]["content"] as $field) {
            $body[$field["label"]] = $field["value"];
        }

        return \http_build_query(data: $body, encoding_type: $this->options["body"]["options"]["encoding"] ?? PHP_QUERY_RFC1738);
    }

    protected function makeMultipartFormData()
    {
        $body = [];

        foreach ($this->options["body"]["content"] as $field) {
            if (\is_string($field["value"])) {
                $body[$field["label"]] = \is_file($field["value"])
                    ? new \CURLFile($field["value"], $field["mime"] ?? null, $field["name"] ?? $field["label"])
                    : $field["value"];

                continue;
            }

            if (\is_resource($field["value"])) {
                $metadata = stream_get_meta_data($field["value"]);

                if ($metadata["wrapper_type"] !== "plainfile") {
                    throw new Exception("[Developer][Exception]: The request body contains invalid file for the field [{$field["label"]}].");
                }

                $body[$field["label"]] = new \CURLFile($metadata["uri"], $field["mime"] ?? null, $field["name"] ?? $field["label"]);
                continue;
            }

            if (\is_array($field["value"])) {
                $field["value"] = \json_encode(value: $field["value"], depth: $field["json"]["depth"]);
                continue;
            }

            $body[$field["label"]] = $field["value"];
        }

        return $body;
    }
}
