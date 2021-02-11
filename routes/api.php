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

    Route::group(['prefix' => 'admin'], function () {
        Route::post('login', 'Auth\UserAuthController@login');

        Route::middleware('auth:users')->group(function () {
            Route::get('user-detail', 'Auth\UserAuthController@getAuthenticatedUser');
            Route::post('refresh-token', 'Auth\UserAuthController@refreshToken');
            Route::post('logout', 'Auth\UserAuthController@logout');

            Route::resource('roles', 'RoleController');
            Route::resource('users', 'UserController');
            Route::post('users/toggle-enable/{slug}', 'UserController@toggleEnable');

            Route::resource('addresses', 'AddressController');
            Route::resource('townships', 'TownshipController');
            Route::resource('cities', 'CityController');
            Route::get('cities/{slug}/townships', 'TownshipController@getTownshipsByCity');

            Route::resource('categories', 'CategoryController');
            Route::resource('sub-categories', 'SubCategoryController');

            Route::resource('restaurant-categories', 'RestaurantCategoryController');
            Route::resource('shop-categories', 'ShopCategoryController');
            Route::get('shop-categories/{slug}/sub-categories', 'SubCategoryController@getSubCategoriesByCategory');

            Route::resource('restaurant-tags', 'RestaurantTagController');
            Route::resource('shop-tags', 'ShopTagController');

            Route::resource('restaurants', 'RestaurantController');
            Route::resource('shops', 'ShopController');
            Route::resource('products', 'ProductController');
        });
    });

    /*
     * -----------
     * Customer API
     * -----------
     */
    require __DIR__ . '/customer-api.php';
});
