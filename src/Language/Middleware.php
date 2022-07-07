<?php

namespace Ezegyfa\LaravelHelperMethods\Language;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class Middleware
{
    public function handle($request, \Closure $next)
    {
        $language = Session::get('language', Config::get('app.locale'));
        App::setLocale($language);
        return $next($request);
    }
}
