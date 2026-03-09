<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpWrapper\Interfaces;

interface HttpClient
{
    public function send(array $request): HttpResponse;

    public function pool(array $requests): array;
}
