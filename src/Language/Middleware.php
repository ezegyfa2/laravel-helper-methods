<?php

namespace Ezegyfa\LaravelHelperMethods\Language;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

class Middleware
{
    public function handle($request, \Closure $next)
    {
        $lastSegment = LanguageMethods::getCurrentUrlLastSegment();
        if (in_array($lastSegment, LanguageMethods::getTranslatedLanguages())) {
            App::setLocale($lastSegment);
            \URL::defaults(['language' => $lastSegment]);
            Session::start();
            Session::put('language', $lastSegment);
            Session::save();
        }
        else if (Session::has('language')) {
            App::setLocale(Session::get('language'));
        }
        return $next($request);
    }
}
