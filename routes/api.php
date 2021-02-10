<?php

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
        Route::get('user-detail', 'Auth\UserAuthController@getAuthenticatedUser');
        Route::post('refresh-token', 'Auth\UserAuthController@refreshToken');
        Route::post('logout', 'Auth\UserAuthController@logout');

        Route::resource('users', 'UserController');
        Route::resource('roles', 'RoleController');

        Route::resource('categories', 'CategoryController');
        Route::resource('sub-categories', 'SubCategoryController');
        Route::resource('sub-categories', 'SubCategoryController');
        Route::get('shop-categories/{slug?}/sub-categories', 'SubCategoryController@getSubCategoriesByCategory')->name('getSubCategoriesByCategory');
        Route::resource('restaurant-categories', 'RestaurantCategoryController');
        Route::resource('shop-categories', 'ShopCategoryController');
        Route::resource('restaurant-tags', 'RestaurantTagController');
        Route::resource('shop-tags', 'ShopTagController');
        Route::resource('cities', 'CityController');
        Route::resource('townships', 'TownshipController');
        Route::get('cities/{slug?}/townships', 'TownshipController@getTownshipsByCity')->name('getTownshipsByCity');
        Route::resource('restaurants', 'RestaurantController');
        Route::resource('shops', 'ShopController');
        Route::resource('products', 'ProductController');
        Route::resource('addresses', 'AddressController');
        Route::resource('product_variations', 'ProductVariationController');
    });
});
