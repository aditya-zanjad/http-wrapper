<?php

declare(strict_types=1);

namespace AdityaZanjad\Http\Enums;

use AdityaZanjad\Http\Base\Enum;

/**
 * @version 1.0
 */
class Method extends Enum
{
    public const GET        =   'GET';
    public const PUT        =   'PUT';
    public const HEAD       =   'HEAD';
    public const POST       =   'POST';
    public const PATCH      =   'PATCH';
    public const TRACE      =   'TRACE';
    public const DELETE     =   'DELETE';
    public const CONNECT    =   'CONNECT';
    public const OPTIONS    =   'OPTIONS';

    /**
     * Get a comma-separated list of all the constant values.
     *
     * @return string
     */
    public static function join(): string
    {
        return \implode(', ', static::all());
    }
}
