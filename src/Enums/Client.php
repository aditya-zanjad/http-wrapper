<?php

namespace AdityaZanjad\Http\Enums;

use AdityaZanjad\Http\Clients\Curl;
use AdityaZanjad\Http\Clients\Guzzle;
use AdityaZanjad\Http\Clients\Stream;


enum Client: string
{
    case CURL   =   Curl::class;
    case GUZZLE =   Guzzle::class;
    case STREAM =   Stream::class;

    /**
     * Try getting the case value from the given name.
     *
     * @param string $name
     *
     * @return null|string
     */
    public static function tryFromName(string $name): null|string
    {
        $name = strtoupper($name);

        if (!defined("self::{$name}")) {
            return null;
        }

        return constant("self::{$name}");
    }
}
