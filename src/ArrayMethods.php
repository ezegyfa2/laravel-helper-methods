<?php

namespace Ezegyfa\LaravelHelperMethods;

class ArrayMethods
{
    public static function convert(object $object): array
    {
        $array = [];
        foreach ($object as $key => $value) {
            $array[$key] = $value;
        }
        return $array;
    }

    public static function sortArrayByKeysRecursive(&$arrayToSort): bool
    {
        foreach ($arrayToSort as &$value) {
            if (is_array($value)) {
                static::sortArrayByKeysRecursive($value);
            }
        }
        return ksort($arrayToSort);
    }
}
