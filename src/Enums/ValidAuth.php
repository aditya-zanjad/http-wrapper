<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Enums;

enum ValidAuth: String
{
    case BASIC  =   'basic';
    case DIGEST =   'digest';


    public static function values(): array
    {
        return \array_column(static::cases(), 'value');
    }
}
