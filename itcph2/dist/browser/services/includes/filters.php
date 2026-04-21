<?php

require_once $include_path . "defined_index.php";

//Check if variable is defined and not null
function isDefined($var)
{
    return isset($var);
}

//Check if string is empty
function isEmptyString($str)
{
    return isDefined($str) && is_string($str) && $str === '';
}

//Check if value is true
function isNonEmpty($value)
{
    //false values are "", 0, 0.0, "0", NULL, FALSE, [], $var (a variable declared, but without a value)
    return isDefined($value) && !empty($value);
}

//Check if array is not empty
function isNonEmptyArray($arr)
{
    return isNonEmpty($arr) && is_array($arr);
}

//Check if given variable equal to value
function matchValue($var, $val, $strict = false)
{
    if ($strict) {
        return isDefined($var) && isDefined($val) && $var === $val;
    }
    return isDefined($var) && isDefined($val) && $var == $val;
}

// filter out unwanted content from input, $flag may be
// ENT_COMPAT: Will convert double-quotes and leave single-quotes alone
// ENT_QUOTES: Will convert both double and single quotes
// ENT_NOQUOTES: Will leave both double and single quotes unconverted.)
function filter($input, $flag = ENT_COMPAT)
{
    if (is_array($input)) {
        if (isNonEmptyArray($input)) {
            foreach ($input as $key => $val) {
                $input[$key] = is_string($val) ? htmlentities(strip_tags(trim($val)), $flag) : $val;
            }
            return $input;
        }
        return $input;
    }
    $out = trim($input); // Kills needless whitespace
    $out = strip_tags($out); // Kills html tags

    // Convert all applicable characters to HTML entities to protect from from sql injection
    return htmlentities($out, $flag);
}

// get filtered token/otp
function getFilteredToken($token)
{
    $call_unique_code = strtoupper(trim(preg_replace('/\s+/', ' ', $token)));
    $call_unique_code = preg_replace('~[\r\n]+~', '', $call_unique_code);

    return $call_unique_code;
}
