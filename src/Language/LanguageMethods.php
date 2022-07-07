<?php

namespace Ezegyfa\LaravelHelperMethods\Language;

use Ezegyfa\LaravelHelperMethods\FolderMethods;
use Illuminate\Support\Facades\Route;

class LanguageMethods
{
    public static function getTranslatedLanguages() {
        return FolderMethods::getFolderSubFolders(resource_path('lang'));
    }

    public static function registerRoute() {
        Route::get('/language/{newLanguage}', [Controller::class, 'changeLanguage']);
    }
}
