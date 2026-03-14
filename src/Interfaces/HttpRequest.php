<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Interfaces;

interface HttpRequest
{
    public function build(): array;
}
