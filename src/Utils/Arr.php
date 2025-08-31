<?php

namespace AdityaZanjad\Http\Utils;

use Exception;

/**
 * Get the first value from the array
 *
 * @param array<int|string, mixed> $arr
 * 
 * @return mixed
 */
function arr_first(array $arr)
{
    if (empty($arr)) {
        return null;
    }

    if (!function_exists('array_key_first')) {
        reset($arr);
        return current($arr);
    }

    $firstKey = array_key_first($arr);
    return $arr[$firstKey];
}

/**
 * Get the first value from the array based on the evaluation of the given callback.
 *
 * @param   array       $arr
 * @param   callable    $callback
 * 
 * @return  mixed
 */
function arr_first_fn(array $arr, callable $callback)
{
    $filteredResult = null;

    foreach ($arr as $key => $value) {
        $callbackResult = $callback($value, $key);

        if (!is_bool($callbackResult)) {
            throw new Exception("[Developer][Exception]: The callback function must return a boolean value.");
        }

        if ($callbackResult === true) {
            $filteredResult = $value;
            break;
        }
    }

    return $filteredResult;
}

/**
 * Get value from the array based on the given dot notation array path else return the default value.
 * 
 * @param   array<int|string, mixed>    $arr
 * @param   string                      $path
 * @param   mixed                       $default
 * 
 * @return  mixed
 */
function arr_get_or_default(array $arr, string $path, $default)
{
    $keys   =   explode('.', $path);
    $ref    =   &$arr;
    $result =   null;

    foreach ($keys as $key) {
        if (!array_key_exists($key, $ref)) {
            $result = $default;
            break;
        }

        $ref = &$arr[$key];
    }

    return $result;
}

/**
 * Set a value in the array based on the given dot notation path.
 * 
 * @param   array<int|string, mixed>    $arr
 * @param   string                      $path
 * @param   mixed                       $value
 * 
 * @return  array<int|string, mixed>
 */
function arr_set(array $arr, string $path, $value): array
{
    $keys       =   explode('.', $path);
    $ref        =   &$arr;
    $currentKey =   null;

    foreach ($keys as $key) {
        $currentKey = $key;

        if (!isset($ref[$$currentKey]) || !is_array($ref[$currentKey])) {
            $ref[$currentKey] = [];
        }

        $ref = &$ref[$currentKey];
    }

    $ref[$currentKey] = $value;
    return $arr;
}
