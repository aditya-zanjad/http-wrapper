<?php

declare(strict_types=1);

namespace AdityaZanjad\Http\Interfaces;

/**
 * @version 1.0
 */
interface HttpRequest
{
    /**
     * Build the data based upon which we want to make the HTTP request.
     *
     * @return mixed
     */
    public function build();
}
