<?php

namespace Helpers;

use Illuminate\Support\Facades\Auth;

trait ModelMethods
{
    public static function getIdByName(string $name)
    {
        return static::getByName($name)->id;
    }

    public static function getByName(string $name)
    {
        return static::where('name', $name)->first();
    }

    public static function getCurrentUserItemIds()
    {
        return static::where('user_id', Auth::user()->id)
            ->select('id')
            ->get()
            ->map(function ($resultItem) {
                return $resultItem->id;
            });
    }
}
