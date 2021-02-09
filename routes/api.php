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
Route::get('sub-categories/filter/{param}', 'SubCategoryController@search')->name('search');
Route::resource('restaurant-categories', 'RestaurantCategoryController');
Route::get('restaurant-categories/filter/{param}', 'RestaurantCategoryController@search')->name('search');
Route::resource('store-categories', 'StoreCategoryController');
Route::get('store-categories/filter/{param}', 'StoreCategoryController@search')->name('search');
Route::get('store-categories/{slug?}/sub-categories', 'StoreCategoryController@getSubCategoriesByStoreCategory')->name('getSubCategoriesByStoreCategory');
Route::resource('tags', 'TagController');
Route::get('tags/filter/{param}', 'TagController@search')->name('search');
Route::resource('cities', 'CityController');
Route::get('cities/filter/{param}', 'CityController@search')->name('search');
Route::resource('townships', 'TownshipController');
Route::get('townships/filter/{param}', 'TownshipController@search')->name('search');
Route::get('cities/{slug?}/townships', 'TownshipController@getTownshipsByCity')->name('getTownshipsByCity');