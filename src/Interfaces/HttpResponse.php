<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpWrapper\Interfaces;

interface HttpResponse
{
    public function code(): int;

    public function status(): null|string;

    public function header(string $name): null|string|array;

    public function headers(): array;

    public function body(): mixed;
}
