<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters;

use Exception;
use AdityaZanjad\HttpAdapters\Adapters\Curl;
use AdityaZanjad\HttpAdapters\Interfaces\HttpAdapter;

/**
 * @version 1.0
 */
class Http
{
    /**
     * Prevent instantiation to serve only as a factory class.
     * 
     * This class serves as a factory class for two reasons. The first reason is that it serves as a common entry
     * point for the users of this package. The second reason is that it's suppossed to provide the objects
     * of the adapter class(es). Therefore, instanting this class just to give out an object of another
     * class doesn't make any sense.
     * 
     * @throws \Exception => If the user tries to instantiate this class.
     */
    public function __construct()
    {
        throw new \Exception("[Developer][Exception]: This class is purposefully kept non-instantiable. Please use its static methods instead.");
    }

    /**
     * Initialize the HTTP adapter to send external HTTP requests.
     * 
     * @param   string                  $adapter    =>  The name of the HTTP adapter to use. Default to 'curl'.
     * @param   array<string, mixed>    $options    =>  Additional configuration parameters to pass to the HTTP adapter.
     * 
     * @throws  \Exception                          =>  If the given adapter name is invalid.
     * 
     * @return  \AdityaZanjad\HttpAdapters\Interfaces\HttpAdapter
     */
    public static function adapter(string $adapter = 'auto', array $options = []): HttpAdapter
    {
        return match (\strtolower($adapter)) {
            'curl', 'auto'  =>  new Curl($options),
            default         =>  throw new Exception("[Developer][Exception]: The HTTP adapter name [{$adapter}] is either unsupported/invalid.")
        };
    }
}
