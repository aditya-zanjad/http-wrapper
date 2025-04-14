<?php

declare (strict_types=1);

namespace AdityaZanjad\Http\Enums;

use Exception;
use AdityaZanjad\Http\Base\Enum;
use GuzzleHttp\Client as GuzzleClient;
use AdityaZanjad\Http\Clients\Curl\Curl;
use AdityaZanjad\Http\Clients\Guzzle\Guzzle;
use AdityaZanjad\Http\Clients\Stream\Stream;

/**
 * @version 2.0
 */
class Provider extends Enum
{
    public const CURL   =   'CURL';
    public const GUZZLE =   'GUZZLE';
    public const STREAM =   'STREAM';

    /**
     * @inheritDoc
     *
     * @throws \Exception
     *
     * @return string
     */
    public static function valueOf(string $key, bool $upperCased = true): string
    {
        $transformedKey = $upperCased ? strtoupper($key) : strtolower($key);

        if ($transformedKey === 'AUTO') {
            return static::autoSelectProvider();
        }

        if (!static::exists($transformedKey)) {
            throw new Exception('[Developer][Exception]: The HTTP client "'. $key .'" does not exist.');
        }

        switch ($transformedKey) {
            case 'GUZZLE':
                if (!class_exists(GuzzleClient::class)) {
                    throw new Exception('[Developer][Exception]: The HTTP client class "' . GuzzleClient::class . '" either does not exist OR is not auto-loaded properly.');
                }
                return Guzzle::class;

            case 'CURL':
                if (!extension_loaded('CURL')) {
                    throw new Exception('[Developer][Exception]: The HTTP client "' . CURL::class . '" requires the PHP extension "php-curl" to be properly loaded.');
                }
                return Curl::class;

            case 'STREAM':
                return Stream::class;

            default:
                throw new Exception("[Developer][Exception]: The HTTP client wrapper \"{$transformedKey}\" does not exist or is invalid.");
        }
    }

    /**
     * Auto-Select the HTTP client class to utilize to send the HTTP request(s).
     *
     * @return string
     */
    public static function autoSelectProvider(): string
    {
        // Try loading any available HTTP clients depending on the following order.
        if (class_exists(GuzzleClient::class)) {
            return Guzzle::class;
        }

        if (extension_loaded('CURL')) {
            return Curl::class;
        }

        return Stream::class;
    }
}
