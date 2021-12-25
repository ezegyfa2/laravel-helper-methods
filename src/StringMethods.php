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
}