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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::resource('categories', 'CategoryController');
Route::resource('sub-categories', 'SubCategoryController');
Route::resource('cities', 'CityController');
Route::resource('townships', 'TownshipController');

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
