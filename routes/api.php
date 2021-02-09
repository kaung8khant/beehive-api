<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'v2', 'middleware' => ['cors', 'json.response']], function () {
    Route::post('login', 'Auth\UserAuthController@login');

    Route::middleware('auth:api')->group(function () {
        Route::post('refresh-token', 'Auth\UserAuthController@refreshToken');
        Route::get('user-detail', 'Auth\UserAuthController@getAuthenticatedUser');
        Route::post('logout', 'Auth\UserAuthController@logout');

        Route::resource('categories', 'CategoryController');
        Route::resource('sub-categories', 'SubCategoryController');
        Route::resource('menus', 'MenuController');
        Route::resource('menuVariations', 'MenuVariationController');
    });
});

// Route::group([
//     'prefix'=> 'categories',
//     'namespace'=> 'App\Http\Controllers',
// ], function () {
//     Route::get('/', 'CategoryController@index');
//     Route::get('/{id}', 'CategoryController@view');
//     Route::post('/', 'CategoryController@create');
//     Route::put('/{id}', 'CategoryController@update');
//     Route::delete('/{id}', 'CategoryController@destroy');
// });
