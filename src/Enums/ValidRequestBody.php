<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Enums;

enum ValidRequestBody: String
{
    case JSON       =   'json';
    case PARAMS     =   'params';
    case UPLOAD     =   'upload';
    case MULTIPART  =   'multipart';

    public static function values(): array
    {
        return \array_column(static::cases(), 'value');
    }
}
