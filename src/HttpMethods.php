<?php

namespace Ezegyfa\LaravelHelperMethods;

use Exception;

class HttpMethods
{
    public static function getFormResponseFromException(Exception $e)
    {
        return response([
            'message' => $e->getMessage(),
        ], 422);
    }
}
