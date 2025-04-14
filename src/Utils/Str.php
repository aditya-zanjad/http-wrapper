<?php

namespace AdityaZanjad\Http\Utils;

/**
 * Check if the given string contains the specified substring.
 *
 * @param   string  $str
 * @param   string  $sub
 * 
 * @return  string
 */
function str_contains_v2(string $str, string $sub)
{
    if (!function_exists('str_contains')) {
        return strpos($str, $sub);
    }
    
    return str_contains($str, $sub);
}

/**
 * Replace the search string with the specified replace string in the given string.
 *
 * @param   string  $str
 * @param   string  $search
 * @param   string  $replace
 * 
 * @return  string
 */
function str_replace_v2(string $str, string $search, string $replace)
{
    return str_replace($search, $replace, $str);
}


/**
 * Generate a unique string based on the given characters length.
 * 
 * @param string $length
 * 
 * @return string
 */
function str_random(int $length = 30)
{
    $divisionResult = $length / 2;

    $approxHalfLength = is_float($divisionResult) 
        ? (floor($divisionResult) + 1) 
        : $divisionResult;

    $randomBytes = function_exists('random_bytes') 
        ? random_bytes($approxHalfLength) 
        : openssl_random_pseudo_bytes($approxHalfLength);

    return bin2hex($randomBytes);
}
