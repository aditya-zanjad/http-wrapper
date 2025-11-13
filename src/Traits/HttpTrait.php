<?php

declare (strict_types=1);

namespace AdityaZanjad\Http\Traits;

use AdityaZanjad\Http\Http;

/**
 * @version 2.0
 */
trait HttpTrait
{
    /**
     * Create an instance of a HTTP client by the given name.
     *
     * @param array<string, mixed> $config
     *
     * @return \AdityaZanjad\Http\Http
     */
    final protected function http(array $config): Http
    {
        return new Http($config);
    }
}
