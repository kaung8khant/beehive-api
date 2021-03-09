<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'vendor'], function () {
    Route::post('login', 'Auth\UserAuthController@login');
    Route::middleware(['auth:users', 'user.enable'])->group(function () {
        Route::get('profile', 'Auth\UserAuthController@getProfile');
        Route::put('profile/update', 'Auth\UserAuthController@updateProfile');
        Route::post('refresh-token', 'Auth\UserAuthController@refreshToken');
        Route::post('logout', 'Auth\UserAuthController@logout');
    });
});
