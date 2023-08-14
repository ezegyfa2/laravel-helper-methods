<?php
Route::group(['middleware' => 'guest', 'controller' => 'User\UserAuthenticationController'], function() {
    Route::get('/login', 'loginPage')->name('loginPage');
    Route::post('/login', 'login')->name('login');
    Route::get('/registration', 'registrationPage')->name('registrationPage');
    Route::post('/registration', 'registration')->name('registration');
});

Route::get('/logout', 'User\UserAuthenticationController@logout')->name('logout');

Route::group(['controller' => 'Admin\AdminAuthenticationController', 'prefix' => 'admin', 'as' => 'admin.'], function() {
    Route::get('/login', 'loginPage')->name('loginPage');
    Route::post('/login', 'login')->name('login');

    Route::middleware('adminAuth')->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');//->middleware(['role:MainAdmin,admin']);
    });

    Route::get('/logout', 'logout')->name('logout');
});