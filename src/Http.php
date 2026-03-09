<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpWrapper;

use AdityaZanjad\HttpWrapper\Providers\Curl;

class Http
{
    public static function client(array $options = []): HttpClient
    {
        $provider = \strtolower($options['provider'] ?? 'auto');
        
        return match ($provider) {
            'curl', 'auto'  =>  new Curl($options),
            default         =>  throw new Exception("[Developer][Exception]: The HTTP provider [{$provider}] is invalid.")
        };
    }
}
