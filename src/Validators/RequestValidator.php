<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Validators;

use AdityaZanjad\HttpAdapters\Enums\ValidAuth;
use Exception;
use AdityaZanjad\HttpAdapters\Enums\ValidRequestBody;
use AdityaZanjad\HttpAdapters\Enums\ValidQueryEncoding;
use AdityaZanjad\HttpAdapters\Enums\ValidRequestMethod;
use AdityaZanjad\HttpAdapters\Enums\ValidMultipartField;

class RequestValidator
{
    public function __construct(protected array $data)
    {
        //
    }

    public function validate(): void
    {
        $this->validateUrl();
        $this->validateQuery();
        $this->validateMethod();
        $this->validateHeaders();
        $this->validateBody();
    }

    protected function validateUrl(): void
    {
        if (!isset($this->data['url'])) {
            throw new Exception("[Developer][Exception]: The parameter url is required.");
        }

        if (\filter_var($this->data['url'], FILTER_VALIDATE_URL) === false) {
            throw new Exception("[Developer][Exception]: The parameter url must be a valid URL.");
        }
    }

    protected function validateQuery(): void
    {
        # query
        if (!isset($this->data['query'])) {
            return;
        }

        if (!\is_array($this->data['query'])) {
            throw new Exception("[Developer][Exception]: The parameter query must be an array.");
        }

        if (empty($this->data['query'])) {
            throw new Exception("[Developer][Exception]: The parameter query must not be empty.");
        }

        # query.prefix
        $this->validateQueryPrefix($this->data, 'query');

        # query.separator
        $this->validateQuerySeparator($this->data, 'query');

        # query.encoding
        $this->validateQueryPrefix($this->data, 'query');
    }

    protected function validateMethod(): void
    {
        if (!isset($this->data['method'])) {
            return;
        }

        if (!\is_string($this->data['method'])) {
            throw new Exception("[Developer][Exception]: The parameter method is required.");
        }

        if (empty($this->data['method'])) {
            throw new Exception("[Developer][Exception]: The parameter method must not be empty.");
        }

        $validMethods = ValidRequestMethod::values();

        if (!\in_array($this->data['method'], $validMethods, true)) {
            $validMethods = \implode(', ', $validMethods);
            throw new Exception("[Developer][Exception]: The parameter method must be one of these values: {$validMethods}");
        }
    }

    protected function validateHeaders(): void
    {
        if (!isset($this->data['headers'])) {
            return;
        }

        if (!\is_array($this->data['headers'])) {
            throw new Exception("[Developer][Exception]: The parameter headers must be an array.");
        }

        if (empty($this->data['headers'])) {
            throw new Exception("[Developer][Exception]: The parameter headers must not be empty.");
        }
    }

    protected function validateBody(): void
    {
        if (!isset($this->data['body'])) {
            return;
        }

        if (!isset($this->data['body.type'])) {
            return;
        }

        switch ($this->data['body.type']) {
            case ValidRequestBody::JSON->value:
                $this->validateBodyIsFilledArr();
                break;

            case ValidRequestBody::PARAMS->value:
                $this->validateBodyIsFilledArr();
                $this->validateQueryPrefix($this->data['body.form.prefix'] ?? [], 'body.form.prefix');
                $this->validateQuerySeparator($this->data['body.form.separator'] ?? [], 'body.form.separator');
                $this->validateQueryEncoding($this->data['body.form.encoding'] ?? [], 'body.form.encoding');
                break;

            case ValidRequestBody::MULTIPART->value:
                $this->validateBodyIsFilledArr();
                $this->validateMultipartBody();
                break;

            case ValidRequestBody::UPLOAD->value:
                $this->validateBodyIsUpload();
                break;

            default:
                $validRequestBodies = \implode(', ', ValidRequestBody::values());
                throw new Exception("[Developer][Exception]: The parameter body.type is set to an invalid value: {$validRequestBodies}");
        }
    }

    protected function validateAuthOptions(): void
    {
        # auth
        if (!isset($this->data['auth'])) {
            return;
        }

        $validAuthTypes = ValidAuth::values();

        if (!\in_array($this->data['auth'], $validAuthTypes, true)) {
            $validAuthTypes = \implode('', $validAuthTypes);
            throw new Exception("[Developer][Exception]: The parameter auth must be one of these values: {$validAuthTypes}");
        }

        # auth.username
        if (!isset($this->data['auth.username'])) {
            throw new Exception("[Developer][Exception]: The parameter auth.username is required.");
        }

        if (!\is_string($this->data['auth.username'])) {
            throw new Exception("[Developer][Exception]: The parameter auth.username must be a string.");
        }

        if (empty($this->data['auth.username'])) {
            throw new Exception("[Developer][Exception]: The parameter auth.username must not be empty.");
        }

        # auth.password
        if (!isset($this->data['auth.password'])) {
            throw new Exception("[Developer][Exception]: The parameter auth.password is required.");
        }

        if (!\is_string($this->data['auth.password'])) {
            throw new Exception("[Developer][Exception]: The parameter auth.password must be a string.");
        }

        if (empty($this->data['auth.password'])) {
            throw new Exception("[Developer][Exception]: The parameter auth.password must not be empty.");
        }
    }

    private function validateBodyIsUpload(): void
    {
        if (\is_string($this->data['body'])) {
            if (!\is_file($this->data['body'])) {
                throw new Exception("[Developer][Exception]: The parameter body must be a path to a VALID FILE.");
            }

            if (!\is_readable($this->data['body'])) {
                throw new Exception("[Developer][Exception]: The parameter body must contain a path to a VALID READABLE FILE.");
            }
        }

        if (!\is_resource($this->data['body'])) {
            throw new Exception("[Developer][Exception]: The parameter body must be either a path to a valid file OR must be file handle/resource.");
        }

        $metadata = \stream_get_meta_data($this->data['body']);

        if ($metadata['wrapper_type'] !== 'plainfile') {
            throw new Exception("[Developer][Exception]: The parameter body must be a valid file handle/resource.");
        }
    }

    private function validateBodyIsFilledArr(): void
    {
        if (!isset($this->data['body'])) {
            throw new Exception("[Developer][Exception]: The parameter body is required.");
        }

        if (!\is_array($this->data['body'])) {
            throw new Exception("[Developer][Exception]: The parameter body must be an array.");
        }

        if (empty($this->data['body'])) {
            throw new Exception("[Developer][Exception]: The parameter body must not be empty.");
        }
    }

    private function validateMultipartBody(): void
    {
        foreach ($this->data['body'] as $index => $field) {
            $fieldPath = "body.{$index}";

            # body.*
            if (!\is_array($field)) {
                throw new Exception("[Developer][Exception]: The parameter {$fieldPath} must be an array.");
            }

            # body.*.name
            $this->validateMultipartFieldName($field, $fieldPath);

            # body.*.value OR body.*.value.json OR body.*.value.file
            $this->validateMultipartFieldValue($field, $fieldPath);

            # body.*.mime
            $this->validateMultipartFieldMime($field, $fieldPath);

            # body.*.filename
            $this->validateMultipartFieldFileName($field, $fieldPath);
        }
    }

    protected function validateAcceptEncodingOption(): void
    {
        if (!isset($this->data['encoding.accept'])) {
            return;
        }

        
    }

    private function validateQueryPrefix(array $field, string $fieldPath): void
    {
        if (!isset($field["{$fieldPath}.encoding"])) {
            return;
        }

        if (!\is_string($field["{$fieldPath}.encoding"])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.encoding must be a string.");
        }

        if (empty($field["{$fieldPath}.encoding"])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.encoding must not be empty.");
        }

        $validQueryEncodings = ValidQueryEncoding::values();

        if (!\in_array($field["{$fieldPath}.encoding"], $validQueryEncodings, true)) {
            $validQueryEncodings = \implode(', ', $validQueryEncodings);
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.encoding must be one of these values: {$validQueryEncodings}");
        }
    }

    private function validateQuerySeparator(array $field, string $fieldPath): void
    {
        if (!isset($field["{$fieldPath}.separator"])) {
            return;
        }

        if (!\is_string($field["{$fieldPath}.separator"])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.separator must be a string.");
        }

        if (empty($field["{$fieldPath}.separator"])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.separator must not be empty.");
        }
    }

    private function validateQueryEncoding(array $field, string $fieldPath): void
    {
        if (!isset($field["{$fieldPath}.encoding"])) {
            return;
        }

        if (!\is_string($field["{$fieldPath}.encoding"])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.encoding must be a string.");
        }

        if (empty($field["{$fieldPath}.encoding"])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.encoding must not be empty.");
        }

        $validEncodings = ValidQueryEncoding::values();

        if (!\in_array($field["{$field}.encoding"], $validEncodings, true)) {
            $validEncodings = \implode(', ', $validEncodings);
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.encoding must be one of these values: {$validEncodings}");
        }
    }

    private function validateMultipartFieldName(array $field, string $fieldPath): void
    {
        if (!isset($field['name'])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.name is required.");
        }

        if (!\is_string($field['name'])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.name must be a string.");
        }

        if (empty($field['name'])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.name must not be empty.");
        }
    }

    private function validateMultipartFieldValue(array $field, string $fieldPath): void
    {
        $jsonField = ValidMultipartField::JSON->value;

        # body.*.value OR body.*.value.json
        if (\array_key_exists("value", $field) || \array_key_exists("value.{$jsonField}", $field)) {
            return;
        }

        # body.*.value.file
        $fileField = ValidMultipartField::FILE->value;

        if (!isset($field["value.{$fileField}"])) {
            throw new Exception("[Developer][Exception]: One of these parameters must be present: {$fieldPath}.value, {$fieldPath}.{$jsonField} or {$fieldPath}.{$fileField}.");
        }

        if (\is_string($field['value']) && (!\is_file($field['value']) || !\is_readable($field['value']))) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.value.{$fileField} must be a VALID ACCESSIBLE/READABLE FILE PATH.");
        }

        if (!\is_resource($field['value'])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.value.{$fileField} must be either a VALID FILE PATH OR FILE RESOURCE.");
        }

        $metadata = \stream_get_meta_data($field['value']);

        if ($metadata['wrapper_type'] !== 'plainfile') {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.value.{$fileField} must be a VALID FILE RESOURCE.");
        }
    }

    private function validateMultipartFieldMime(array $field, string $fieldPath): void
    {
        if (!isset($field['mime'])) {
            return;
        }

        if (!\is_string($field['mime'])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.mime must be a string.");
        }

        if (empty($field['mime'])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.mime must not be empty.");
        }
    }

    private function validateMultipartFieldFileName(array $field, string $fieldPath): void
    {
        if (isset($field['filename'])) {
            return;
        }

        if (!\is_string($field['filename'])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.filename must be a string.");
        }

        if (empty($field['filename'])) {
            throw new Exception("[Developer][Exception]: The parameter {$fieldPath}.filename must not be empty.");
        }
    }
}
