<?php

namespace Ezegyfa\LaravelHelperMethods\Language;

use Ezegyfa\LaravelHelperMethods\FolderMethods;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;

class LanguageMethods
{
    public static function getTranslatedLanguages() {
        return FolderMethods::getFolderSubFolders(resource_path('lang'));
    }

    public static function registerRoute() {
        Route::get('/language/{newLanguage}', [Controller::class, 'changeLanguage']);
    }

    public static function checkLanguage() {
        if (request()->has('lang')) {
            if (in_array(request()->get('lang'), static::getTranslatedLanguages())) {
                App::setLocale(request()->get('lang'));
            }
        }
    }
}
