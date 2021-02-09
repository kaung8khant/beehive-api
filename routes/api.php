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
Route::get('shop-categories/{slug?}/sub-categories', 'SubCategoryController@getSubCategoriesByCategory')->name('getSubCategoriesByCategory');
Route::resource('restaurant-categories', 'RestaurantCategoryController');
Route::resource('shop-categories', 'ShopCategoryController');
Route::resource('restaurant-tags', 'RestaurantTagController');
Route::resource('shop-tags', 'ShopTagController');
Route::resource('cities', 'CityController');
Route::resource('townships', 'TownshipController');
Route::get('cities/{slug?}/townships', 'TownshipController@getTownshipsByCity')->name('getTownshipsByCity');
Route::resource('restaurants', 'RestaurantContoller');
Route::resource('shops', 'ShopController');
