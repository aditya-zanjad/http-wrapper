<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Enums;

enum ValidQueryEncoding: string
{
    case RFC_1738 = '1738';
    case RFC_3986 = '3986';

    public static function values(): array
    {
        return \array_column(static::cases(), 'value');
    }
}
