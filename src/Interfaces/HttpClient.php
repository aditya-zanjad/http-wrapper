<?php

namespace AdityaZanjad\Http\Interfaces;

interface HttpClient
{
    /**
     * Get the HTTP status code of the response.
     *
     * @return int
     */
    public function status(): int;

    /**
     * Get the HTTP response reason phrase. For example, OK, NOT FOUND etc.
     *
     * @return string
     */
    public function phrase(): string;

    /**
     * Decode the contents of the received HTTP response.
     *
     * @return mixed
     */
    public function decode(): mixed;

    /**
     * Get all of the headers received in the response.
     *
     * @return array<string, mixed>
     */
    public function headers(): array;

    /**
     * Get value of a particular header.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function header(string $key): mixed;
}
