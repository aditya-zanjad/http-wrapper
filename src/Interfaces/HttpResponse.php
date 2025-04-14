<?php

declare(strict_types=1);

namespace AdityaZanjad\Http\Interfaces;

/**
 * @version 1.0
 */
interface HttpResponse
{
    /**
     * Get the HTTP status code for the received response.
     *
     * @return int
     */
    public function code(): int;

    /**
     * Get the reason phrase for the received response.
     *
     * @return string
     */
    public function status(): string;

    /**
     * Get the value of the HTTP response header by its name.
     *
     * @param string $name
     *
     * @return string|array<int, string>
     */
    public function header(string $name);

    /**
     * Get all of the HTTP response headers.
     *
     * @return array
     */
    public function headers(): array;

    /**
     * Obtain the HTTP response body.
     *
     * @param array<int|string, string|array<int, string>> $options
     *
     * @return mixed
     */
    public function body(array $options = []);
}
