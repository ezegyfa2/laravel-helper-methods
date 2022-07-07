<?php

namespace Ezegyfa\LaravelHelperMethods\Language;

use Illuminate\Support\Facades\Session;

class Controller
{
    public function changeLanguage($newLanguage) {
        if (! in_array($newLanguage, ['en', 'hu', 'ro'])) {
            abort(400);
        }

        Session::start();
        Session::put('language', $newLanguage);
        Session::save();
        $previousUrl = url()->previous();
        if ($previousUrl) {
            return redirect($previousUrl);
        }
        else {
            return redirect('/');
        }
    }
}
