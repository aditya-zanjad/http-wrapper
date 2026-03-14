<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Adapters\Curl;

use Exception;
use AdityaZanjad\HttpAdapters\Interfaces\HttpRequest;

class Request implements HttpRequest
{
    protected array $req;

    public function __construct(protected array $request)
    {
        //
    }

    public function build(): array
    {
        $this->req = [
            CURLOPT_URL => $this->request["url"],
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => $this->request["timeout"] ?? 60,
            CURLOPT_CUSTOMREQUEST => $this->request["method"],
            CURLOPT_RETURNTRANSFER => true,
        ];

        if (
            isset($this->request["headers"]) &&
            !empty($this->request["headers"])
        ) {
            $this->req[CURLOPT_HTTPHEADER] = \array_map(
                fn($value, $name) => "{$name}: {$value}",
                $this->request["headers"],
            );
        }

        if (!isset($this->request["body"]["content"])) {
            return $this->req;
        }

        $contentType = null;

        foreach ($this->request["headers"] as $name => $value) {
            if (\strtolower($name) === "content-type") {
                $contentType = $value;
            }
        }

        if (\is_null($contentType)) {
            throw new Exception(
                "[Developer][Exception]: The header [Content-Type] is required along with the request payload.",
            );
        }

        $this->req[CURLOPT_POSTFIELDS] = match ($contentType) {
            "application/json" => $this->makeJsonContent(),
            "application/x-www-form-urlencoded"
                => $this->makeUrlEncodedFormContent(),
            "multipart/form-data" => $this->makeMultipartFormData(),
            default => throw new Exception(
                "[Developer][Exception]: The request payload type [{$contentType}] is either invalid OR not supported by this package yet.",
            ),
        };

        return $this->req;
    }

    protected function makeJsonContent(): string
    {
        $body = [];

        foreach ($this->request["body"]["content"] as $field) {
            $body[$field["label"]] = $field["value"];
        }

        return \json_encode(
            value: $body,
            depth: $this->request["body"]["options"]["depth"] ?? 1024,
        );
    }

    protected function makeUrlEncodedFormContent()
    {
        $body = [];

        foreach ($this->request["body"]["content"] as $field) {
            $body[$field["label"]] = $field["value"];
        }

        return \http_build_query(
            data: $body,
            encoding_type: $this->request["body"]["options"]["encoding"] ??
                PHP_QUERY_RFC1738,
        );
    }

    protected function makeMultipartFormData()
    {
        $body = [];

        foreach ($this->request["body"]["content"] as $field) {
            if (\is_string($field["value"])) {
                if (\is_file($field["value"])) {
                    $body[$field["label"]] = new \CURLFile(
                        $field["value"],
                        $field["mime"] ?? null,
                        $field["name"] ?? $field["label"],
                    );
                }

                $body[$field["label"]] = $field["value"];
                continue;
            }

            if (\is_resource($field["value"])) {
                $metadata = stream_get_meta_data($field["value"]);

                if ($metadata["wrapper_type"] !== "plainfile") {
                    throw new Exception(
                        "[Developer][Exception]: The request body contains invalid file for the field [{$field["label"]}].",
                    );
                }

                $body[$field["label"]] = new \CURLFile(
                    $metadata["uri"],
                    $field["mime"] ?? null,
                    $field["name"] ?? $field["label"],
                );
                continue;
            }

            if (\is_array($field["value"])) {
                $field["value"] = \json_encode(
                    value: $field["value"],
                    depth: $field["json"]["depth"],
                );
            }

            $body[$field["label"]] = $field["value"];
        }

        return $body;
    }
}
