<?php

namespace Ezegyfa\LaravelHelperMethods;

class StringMethods
{
    public static function concatenateStrings(Array $stringsToConcatenate, string $concatenator)
    {
        $result = '';
        foreach ($stringsToConcatenate as $stringToConcatenate) {
            $result .= $stringToConcatenate . $concatenator;
        }
        return substr($result, 0, strlen($result) - strlen($concatenator));
    }

    public static function shortString(string $text, $maxLength = 50)
    {
        if (strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength, 'UTF-8') . '...';
        }
        else {
            return $text;
        }
    }
}