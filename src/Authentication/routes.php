<?php

if (config('auth.loginControllerPath')) {
    Route::group([ 'middleware' => 'guest', 'controller' => config('auth.loginControllerPath') ], function() {
        Route::get('/login', 'loginPage')->name('loginPage');
        Route::post('/login', 'login')->name('login');
        Route::get('/registration', 'registrationPage')->name('registrationPage');
        Route::post('/registration', 'registration')->name('registration');
    });

    Route::get('/logout', config('auth.loginControllerPath') . '@logout')->name('logout');
}

if (config('auth.adminLoginControllerPath')) {
    Route::group([ 'controller' => config('auth.adminLoginControllerPath'), 'prefix' => 'admin', 'as' => 'admin.' ], function() {
        Route::get('/login', 'loginPage')->name('loginPage');
        Route::post('/login', 'login')->name('login');
    
        Route::middleware('adminAuth')->group(function () {
            Route::get('/', 'dashboard')->name('dashboard');//->middleware(['role:MainAdmin,admin']);
        });
    
        Route::get('/logout', 'logout')->name('logout');
    });
}
