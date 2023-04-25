<?php

namespace Ezegyfa\LaravelHelperMethods;

class CheckingMethods
{
    public static function checkConfigValueIsSet(string $configKey)
    {
        if (!config()->has($configKey)) {
            throw new \Exception($configKey . ' config value must set');
        }
    }
}
