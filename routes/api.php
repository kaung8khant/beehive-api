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

Route::resource('sub-categories', 'SubCategoryController');
Route::resource('restaurant-categories', 'RestaurantCategoryController');
Route::resource('store-categories', 'StoreCategoryController');
Route::get('store-categories/{slug?}/sub-catrgories', 'StoreCategoryController@getSubCategoriesByStoreCategory')->name('getSubCategoriesByStoreCategory');
Route::resource('tags', 'TagController');
Route::resource('cities', 'CityController');
Route::resource('townships', 'TownshipController');
