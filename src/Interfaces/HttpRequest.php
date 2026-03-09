<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpWrapper\Interfaces;

interface HttpRequest
{
    public function build(): array;
}
