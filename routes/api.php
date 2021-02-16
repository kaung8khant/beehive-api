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

        Route::middleware(['auth:users', 'user.enable'])->group(function () {
            Route::get('user-detail', 'Auth\UserAuthController@getAuthenticatedUser');
            Route::post('refresh-token', 'Auth\UserAuthController@refreshToken');
            Route::post('logout', 'Auth\UserAuthController@logout');

            Route::resource('roles', 'RoleController');
            Route::resource('users', 'UserController');
            Route::patch('users/toggle-enable/{slug}', 'UserController@toggleEnable');

            Route::resource('customers', 'CustomerController');
            Route::patch('customers/toggle-enable/{slug}', 'CustomerController@toggleEnable');

            Route::resource('cities', 'CityController');
            Route::resource('townships', 'TownshipController');
            Route::get('cities/{slug}/townships', 'TownshipController@getTownshipsByCity');

            Route::resource('sub-categories', 'SubCategoryController');

            Route::resource('restaurant-categories', 'RestaurantCategoryController');
            Route::resource('shop-categories', 'ShopCategoryController');
            Route::get('shop-categories/{slug}/sub-categories', 'SubCategoryController@getSubCategoriesByCategory');

            Route::resource('restaurant-tags', 'RestaurantTagController');
            Route::resource('shop-tags', 'ShopTagController');
            Route::get('shops/{slug}/shop-tags', 'ShopTagController@getTagsByShop');

            Route::resource('restaurants', 'RestaurantController');
            Route::get('restaurants/{slug}/restaurant-tags', 'RestaurantTagController@getTagsByRestaurant');
            Route::get('restaurants/{slug}/restaurant-categories', 'RestaurantCategoryController@getCategoriesByRestaurant');

            Route::resource('shops', 'ShopController');
            Route::get('shops/{slug}/shop-categories', 'ShopCategoryController@getCategoriesByShop');

            Route::resource('products', 'ProductController');

            Route::resource('menus', 'MenuController');
            Route::resource('menu-variations', 'MenuVariationController');
            Route::resource('menu-variation-values', 'MenuVariationValueController');
            Route::resource('menu-toppings', 'MenuToppingController');
            Route::resource('menu-topping-values', 'MenuToppingValueController');

            Route::resource('product-variations', 'ProductVariationController');
            Route::resource('product-variation-values', 'ProductVariationValueController');

            Route::resource('restaurant-branches', 'RestaurantBranchController');
            Route::get('restaurants/{slug}/restaurant-branches', 'RestaurantBranchController@getBranchesByRestaurant');
            Route::get('townships/{slug}/restaurant-branches', 'RestaurantBranchController@getBranchesByTownship');

            Route::resource('shop-branches', 'ShopBranchController');
            Route::get('shops/{slug}/shop-branches', 'ShopBranchController@getBranchesByShop')->name('getBranchesByShop');
            Route::get('townships/{slug}/shop-branches', 'ShopBranchController@getBranchesByTownship')->name('getBranchesByTownship');

            Route::get('settings', 'SettingController@index');
            Route::get('settings/{key?}', 'SettingController@show');
            Route::put('settings/update', 'SettingController@update_setting');

            Route::get('profile', 'ProfileController@index');
            Route::put('profile/update', 'ProfileController@update_profile');

            Route::resource('orders', 'OrderController');
            Route::resource('order-contacts', 'OrderContactController');
            Route::resource('order-status', 'OrderStatusController');
            Route::get('orders/{status}/order-status', 'OrderStatusController@getStatusByOrder')->name('getStatusByOrder');
            Route::resource('order-items', 'OrderItemController');

            Route::resource('ratings', 'RatingController');
        });
    });

    /*
     * -----------
     * Customer API
     * -----------
     */
    require __DIR__ . '/customer-api.php';
});
