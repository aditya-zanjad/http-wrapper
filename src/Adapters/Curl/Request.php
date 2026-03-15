<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Adapters\Curl;

use Exception;
use AdityaZanjad\HttpAdapters\Interfaces\HttpRequest;

class Request implements HttpRequest
{
    public function __construct(protected array $request)
    {
        //
    }

    public function build(): array
    {
        $curlRequest = [
            CURLOPT_URL             =>  $this->request['url'],
            CURLOPT_HEADER          =>  false,
            CURLOPT_TIMEOUT         =>  $this->request['timeout'] ?? 30,
            CURLOPT_MAXREDIRS       =>  $this->request['redirects']['max'] ?? 5,
            CURLOPT_CUSTOMREQUEST   =>  $this->request['method'] ?? 'GET',
            CURLOPT_FRESH_CONNECT   =>  $this->request['fresh'] ?? false, 
            CURLOPT_FOLLOWLOCATION  =>  $this->request['redirects']['allow'] ?? true,
            CURLOPT_RETURNTRANSFER  =>  true,
        ];

        if (isset($this->request['version'])) {
            $curlRequest[CURLOPT_HTTP_VERSION] = $this->request['version'];
        }

        if (isset($this->request['read_timeout'])) {
            $curlRequest[CURLOPT_LOW_SPEED_LIMIT]   =   1;
            $curlRequest[CURLOPT_LOW_SPEED_TIME]    =   $this->request['read_timeout'];
        }

        if (isset($this->requests['query'])) {
            $curlRequest[CURLOPT_URL] .= \http_build_query(
                $this->request['query']['params'],
                $this->request['query']['options']['prefix'] ?? '',
                $this->request['query']['options']['separator'] ?? null,
                $this->request['query']['options']['encoding'] ?? PHP_QUERY_RFC1738,
            );
        }

        if (isset($this->request["headers"])) {
            $curlRequest[CURLOPT_HTTPHEADER] = $this->makeHeaders();
        }

        if (isset($this->request['auth'])) {
            $curlRequest += $this->makeAuthData();
        }

        if (isset($this->request['cookies']['input'])) {
            $curlRequest[CURLOPT_COOKIEFILE] = $this->request['cookies']['input'];
        }

        if (isset($this->request['cookies']['output'])) {
            $curlRequest[CURLOPT_COOKIEJAR] = $this->request['cookies']['output'];
        }

        if (isset($this->request['ssl']['certificate'])) {
            $curlRequest += $this->makeSslOptions();
        }

        $curlRequest[CURLOPT_ENCODING] = $this->makeAcceptEncodingHeader();

        if (isset($this->request['ssl']['key'])) {
            $curlRequest[CURLOPT_SSLKEY] = $this->request['ssl']['key'];
        }

        if (isset($this->request['progress'])) {
            $curlRequest += $this->makeProgressCallable();
        }

        if (isset($this->request['ip_resolve'])) {
            $curlRequest[CURLOPT_IPRESOLVE] = $this->request['ip_resolve'] === 'v6' ? CURL_IPRESOLVE_V6 : CURL_IPRESOLVE_V4;
        }

        if (isset($this->request["body"]["content"])) {
            $curlRequest += $this->makeBody();
        }

        return $curlRequest;
    }

    protected function makeHeaders(): array
    {
        $headers = [];

        foreach ($this->request['headers'] as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }

        return $headers;
    }

    protected function makeAuthData(): array
    {
        $data = match ($this->request['auth']['method']) {
            'basic'     =>  [CURLOPT_HTTPAUTH => CURLAUTH_BASIC, CURLOPT_USERPWD => "{$this->request['auth']['username']}:{$this->request['auth']['password']}"],
            'digest'    =>  [CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_USERPWD => "{$this->request['auth']['username']}:{$this->request['auth']['password']}"],

            default => throw new Exception("[Developer][Exception]: The HTTP authentication method {$this->request['auth']['method']} is either invalid or not supported yet.")
        };

        return [
            CURLOPT_HTTPAUTH => $data['method']
        ];
    }

    protected function makeSslOptions(): array
    {
        $options                    =   [];
        $options[CURLOPT_SSLCERT]   =   $this->request['ssl']['client']['certificate'];
            
        if (isset($this->request['ssl']['client']['password'])) {
            $options[CURLOPT_SSLCERTPASSWD] = $this->request['ssl']['client']['password'];
        }

        if (isset($this->request['ssl']['client']['key'])) {
            $options[CURLOPT_SSLKEY] = $this->request['ssl']['client']['key'];
        }

        if (isset($this->request['ssl']['verify']['allow']) === false) {
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
        if (!isset($this->request['decode'])) {
            return '';
        }

        if (!\is_string($this->request['decode'])) {
            throw new Exception("[Developer][Exception]: The parameter 'decode' has an invalid value. It should be either true (bool), false (bool) or a string");
        }

        return match ($this->request['decode']) {
            'all'   =>  '',
            'none'  =>  'identity',
            default =>  $this->request['decode']
        };
    }

    protected function makeProgressCallable(): array
    {
        if ($this->request['progress']['allow'] === false) {
            return [
                CURLOPT_NOPROGRESS => true
            ];
        }

        if (!\is_callable($this->request['progress']['callback'])) {
            throw new Exception("[Developer][Exception]: The parameter progress.callback must be a valid PHP callback function.");
        }

        return [
            CURLOPT_NOPROGRESS          =>  false,
            CURLOPT_PROGRESSFUNCTION    =>  $this->request['porgress']['callback']
        ];
    }

    protected function makeProxyOptions(): array
    {
        $options = [
            CURLOPT_PROXY           =>  $this->request['proxy']['url'],
            CURLOPT_HTTPPROXYTUNNEL =>  $this->request['proxy']['tunnel'] ?? false,

            CURLOPT_PROXYTYPE => match ($this->request['proxy']['type'] ?? 'http') {
                'http'      =>  CURLPROXY_HTTP,
                'socks5'    =>  CURLPROXY_SOCKS5,

                default => throw new Exception("[Developer][Exception]: The parameter [proxy.type] has an invalid value. Valid values are: [http, socks5]")
            }
        ];

        if (!isset($this->request['proxy']['auth'])) {
            return $options;
        }

        $options[CURLOPT_PROXYAUTH] = match ($this->request['proxy']['auth']['type'] ?? 'basic') {
            'basic' =>  CURLAUTH_BASIC,
            'ntlm'  =>  CURLAUTH_NTLM,

            default => throw new Exception("[Developer][Exception]: The parameter [proxy.auth.type] has an invalid value. Valid values are: [basic, ntlm]")
        };

        $options[CURLOPT_PROXYUSERPWD] = "{$this->request['proxy']['auth']['username']}:{$this->request['proxy']['auth']['password']}";
        return $options;
    }

    protected function makeBody(): mixed
    {
        $contentType = null;

        foreach ($this->request["headers"] as $name => $value) {
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

        foreach ($this->request["body"]["content"] as $field) {
            $body[$field["label"]] = $field["value"];
        }

        return \json_encode(value: $body, depth: $this->request["body"]["options"]["depth"] ?? 1024);
    }

    protected function makeUrlEncodedFormContent()
    {
        $body = [];

        foreach ($this->request["body"]["content"] as $field) {
            $body[$field["label"]] = $field["value"];
        }

        return \http_build_query(data: $body, encoding_type: $this->request["body"]["options"]["encoding"] ?? PHP_QUERY_RFC1738);
    }

    protected function makeMultipartFormData()
    {
        $body = [];

        foreach ($this->request["body"]["content"] as $field) {
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
