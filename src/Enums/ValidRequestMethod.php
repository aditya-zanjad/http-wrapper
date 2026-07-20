<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Enums;

enum ValidRequestMethod: string
{
    case GET        =   'GET';
    case PUT        =   'PUT';
    case POST       =   'POST';
    case HEAD       =   'HEAD';
    case PATCH      =   'PATCH';
    case TRACE      =   'TRACE';
    case DELETE     =   'DELETE';
    case CONNECT    =   'CONNECT';
    case OPTIONS    =   'OPTIONS';

    public static function values(): array
    {
        return \array_column(static::cases(), 'value');
    }
}