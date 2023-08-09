<?php
Route::middleware('guest')->group(function() {
    Route::get('/login', 'AuthenticationController@loginPage')->name('loginPage');
    Route::post('/login', 'AuthenticationController@login')->name('login');
    Route::get('/registration', 'AuthenticationController@registrationPage')->name('registrationPage');
    Route::post('/registration', 'AuthenticationController@registration')->name('registration');
});

Route::get('/logout', 'AuthenticationController@logout')->name('logout');

Route::group(['prefix' => 'admin'], function () {
    #Route::middleware('guest:admin')->group(function () {
    Route::get('/login', 'AuthenticationController@adminLogin')->name('adminLogin');
    Route::post('/login', 'AuthenticationController@adminLoginPost')->name('adminLoginPost');
    #});

    Route::middleware('adminAuth')->group(function () {
        Route::get('/', 'AuthenticationController@adminDashboard')->name('adminDashboard');
    });

    Route::get('/logout', 'AuthenticationController@logout')->name('adminLogout');
});