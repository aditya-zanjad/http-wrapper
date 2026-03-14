<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Interfaces;

interface HttpAdapter
{
    public function send(array $request): HttpResponse;

    public function pool(array $requests): array;
}
