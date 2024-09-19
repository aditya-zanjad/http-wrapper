<?php

namespace AdityaZanjad\Http\Interfaces;

interface HttpClient
{
    /**
     * Send the HTTP request & return the object of the current HTTP Client class.
     *
     * @return \AdityaZanjad\Http\Interfaces\HttpClient
     */
    public function send(): HttpClient;

    /**
     * Get the HTTP response reason phrase.
     *
     * @return string
     */
    public function status(): string;

    /**
     * Get the HTTP status code of the response.
     *
     * @return int
     */
    public function code(): int;

    /**
     * Decode the contents of the received HTTP response.
     *
     * @return mixed
     */
    public function body(): mixed;

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
