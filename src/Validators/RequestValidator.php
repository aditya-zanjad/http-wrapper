<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Adapters\Curl;

use Exception;

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
        # query.params
        if (!isset($this->data['query']['params'])) {
            return;
        }

        if (!\is_array($this->data['query']['params'])) {
            throw new Exception("[Developer][Exception]: The parameter query.params must be an array.");
        }

        if (empty($this->data['query']['params'])) {
            throw new Exception("[Developer][Exception]: The parameter query.params must not be empty.");
        }

        # query.separator
        if (isset($this->data['query']['separator'])) {
            if (!\is_string($this->data['query']['separator'])) {
                throw new Exception("[Developer][Exception]: The parameter query.separator must be a string.");
            }

            if (empty($this->data['query']['separator'])) {
                throw new Exception("[Developer][Exception]: The parameter query.separator must not be empty.");
            }
        }

        # query.encoding
        if (isset($this->data['query']['encoding'])) {
            if (!\is_string($this->data['query']['encoding'])) {
                throw new Exception("[Developer][Exception]: The parameter query.encoding must be a string.");
            }

            if (empty($this->data['query']['encoding'])) {
                throw new Exception("[Developer][Exception]: The parameter query.encoding must not be empty.");
            }

            $validEncodings = ['1738', '3986'];

            if (!\in_array($this->data['query']['encoding'], $validEncodings, true)) {
                $validEncodings = \implode(', ', $validEncodings);
                throw new Exception("[Developer][Exception]: The parameter query.encoding must be one of these values: {$validEncodings}");
            }
        }

        # query.prefix
        if (isset($this->data['query']['encoding'])) {
            if (!\is_string($this->data['query']['encoding'])) {
                throw new Exception("[Developer][Exception]: The parameter query.encoding must be a string.");
            }

            if (empty($this->data['query']['encoding'])) {
                throw new Exception("[Developer][Exception]: The parameter query.encoding must not be empty.");
            }
        }
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

        $validMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'CONNECT', 'HEAD', 'TRACE'];

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
            throw new Exception("[Developer][Exception]: The parameter query.encoding must not be empty.");
        }
    }

    protected function validateBody(): void
    {
        if (!isset($this->data['body'])) {
            return;
        }

        if (!\is_array($this->data['body'])) {
            throw new Exception("[Developer][Exception]: The parameter body must be an array.");
        }

        if (empty($this->data['body'])) {
            throw new Exception("[Developer][Exception]: The parameter body must not be empty.");
        }

        if (isset($this->data['body']['type'])) {
            if (!\is_string($this->data['body']['type'])) {
                throw new Exception("[Developer][Exception]: The parameter body.type must be a string.");
            }

            $validTypes = ['params', 'json', 'multipart', 'upload'];

            if (!\in_array($this->data['body']['type'], $validTypes, true)) {
                $validTypes = \implode(', ', $validTypes);
                throw new Exception("[Developer][Exception]: The parameter body.type must be one of the values: {$validTypes}");
            }

            match ($this->data['body']['type']) {
                'json'      =>  $this->validateJsonBody(),
                'params'    =>  $this->validateFormParamsBody()
            };
        }
    }

    protected function validateBodyContentIsFilledArray(): void
    {
        if (!isset($this->data['body']['content'])) {
            throw new Exception("[Developer][Exception]: The parameter body.content is required.");
        }

        if (!\is_array($this->data['body']['content'])) {
            throw new Exception("[Developer][Exception]: The parameter body.content must be an array.");
        }

        if (empty($this->data['body']['content'])) {
            throw new Exception("[Developer][Exception]: The parameter body.content must not be empty.");
        }
    }

    protected function validateJsonBody(): void
    {
        # body.content
        $this->validateBodyContentIsFilledArray();

        # body.flags
        if (isset($this->data['body']['flags'])) {
            if (\filter_var($this->data['body']['flags'], FILTER_VALIDATE_INT) === false) {
                throw new Exception("[Developer][Exception]: The parameter body.flags must be an integer value.");
            }
        }

        # body.depth
        if (isset($this->data['body']['depth'])) {
            if (\filter_var($this->data['body']['depth'], FILTER_VALIDATE_INT) === false) {
                throw new Exception("[Developer][Exception]: The parameter body.depth must be an integer value.");
            }
        }
    }

    protected function validateFormParamsBody(): void
    {
        # body.content
        $this->validateBodyContentIsFilledArray();

        # body.separator
        if (isset($this->data['body']['separator'])) {
            if (!\is_string($this->data['body']['separator'])) {
                throw new Exception("[Developer][Exception]: The parameter body.separator must be a string.");
            }

            if (empty($this->data['body']['separator'])) {
                throw new Exception("[Developer][Exception]: The parameter body.separator must not be empty.");
            }
        }

        # body.encoding
        if (isset($this->data['body']['encoding'])) {
            if (!\is_string($this->data['body']['encoding'])) {
                throw new Exception("[Developer][Exception]: The parameter body.encoding must be a string.");
            }

            if (empty($this->data['body']['encoding'])) {
                throw new Exception("[Developer][Exception]: The parameter body.encoding must not be empty.");
            }

            $validEncodings = ['1738', '3986'];

            if (!\in_array($this->data['body']['encoding'], $validEncodings, true)) {
                $validEncodings = \implode(', ', $validEncodings);
                throw new Exception("[Developer][Exception]: The parameter body.encoding must be one of these values: {$validEncodings}");
            }
        }

        # body.prefix
        if (isset($this->data['body']['encoding'])) {
            if (!\is_string($this->data['body']['encoding'])) {
                throw new Exception("[Developer][Exception]: The parameter body.encoding must be a string.");
            }

            if (empty($this->data['body']['encoding'])) {
                throw new Exception("[Developer][Exception]: The parameter body.encoding must not be empty.");
            }
        }
    }
}
