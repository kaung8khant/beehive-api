<?php

use App\Models\Rating;
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
            Route::get('profile', 'Auth\UserAuthController@getProfile');
            Route::put('profile/update', 'Auth\UserAuthController@updateProfile');
            Route::post('refresh-token', 'Auth\UserAuthController@refreshToken');
            Route::post('logout', 'Auth\UserAuthController@logout');

            Route::get('settings', 'SettingController@index');
            Route::get('settings/{key}', 'SettingController@show');
            Route::put('settings/update', 'SettingController@updateSetting');

            Route::resource('roles', 'RoleController');
            Route::resource('users', 'UserController');
            Route::patch('users/toggle-enable/{slug}', 'UserController@toggleEnable');

            Route::resource('customers', 'CustomerController');
            Route::patch('customers/toggle-enable/{slug}', 'CustomerController@toggleEnable');

            Route::resource('cities', 'CityController');
            Route::resource('townships', 'TownshipController');
            Route::get('cities/{slug}/townships', 'TownshipController@getTownshipsByCity');

            /* Shop */
            Route::resource('shop-categories', 'ShopCategoryController');
            Route::resource('sub-categories', 'SubCategoryController');
            Route::resource('shop-tags', 'ShopTagController');
            Route::resource('shops', 'ShopController');
            Route::patch('shops/toggle-enable/{slug}', 'ShopController@toggleEnable');
            Route::get('shop-categories/{slug}/sub-categories', 'SubCategoryController@getSubCategoriesByCategory');
            Route::get('shops/{slug}/shop-categories', 'ShopCategoryController@getCategoriesByShop');
            Route::get('shops/{slug}/shop-tags', 'ShopTagController@getTagsByShop');
            Route::resource('products', 'ProductController');
            Route::resource('product-variations', 'ProductVariationController');
            Route::resource('product-variation-values', 'ProductVariationValueController');
            Route::resource('shop-branches', 'ShopBranchController');
            Route::patch('shop-branches/toggle-enable/{slug}', 'ShopBranchController@toggleEnable');
            Route::get('shops/{slug}/shop-branches', 'ShopBranchController@getBranchesByShop');
            Route::get('townships/{slug}/shop-branches', 'ShopBranchController@getBranchesByTownship');

            /* Shop */

            /* Restaurant */
            Route::resource('restaurant-categories', 'RestaurantCategoryController');
            Route::resource('restaurant-tags', 'RestaurantTagController');
            Route::resource('restaurants', 'RestaurantController');
            Route::patch('restaurants/toggle-enable/{slug}', 'RestaurantController@toggleEnable');
            Route::get('restaurants/{slug}/restaurant-categories', 'RestaurantCategoryController@getCategoriesByRestaurant');
            Route::get('restaurants/{slug}/restaurant-tags', 'RestaurantTagController@getTagsByRestaurant');
            Route::resource('menus', 'MenuController');
            Route::get('menus/{slug}/menu-toppings', 'MenuToppingController@getToppingsByMenu');
            Route::resource('menu-variations', 'MenuVariationController');
            Route::resource('menu-variation-values', 'MenuVariationValueController');
            Route::resource('menu-toppings', 'MenuToppingController');
            Route::resource('menu-topping-values', 'MenuToppingValueController');
            Route::resource('restaurant-branches', 'RestaurantBranchController');
            Route::patch('restaurant-branches/toggle-enable/{slug}', 'RestaurantBranchController@toggleEnable');
            Route::get('restaurants/{slug}/restaurant-branches', 'RestaurantBranchController@getBranchesByRestaurant');
            Route::get('townships/{slug}/restaurant-branches', 'RestaurantBranchController@getBranchesByTownship');
            /* Restaurant */

            Route::resource('orders', 'OrderController');
            Route::resource('order-contacts', 'OrderContactController');
            Route::resource('order-status', 'OrderStatusController');
            Route::get('orders/{status}/order-status', 'OrderStatusController@getStatusByOrder');
            Route::resource('order-items', 'OrderItemController');

            Route::resource('ratings', 'RatingController');
            Route::get('orders/{receiverType}/ratings', 'RatingController@getReceiverTypeByOrder');
        });
    });

    Route::post('upload-file', 'File\UploadController@upload');
    Route::get('files/{source}/{sourceSlug}', 'File\FileController@getFilesBySource');
    Route::get('files/{slug}', 'File\FileController@getFile');

    Route::get('images/{source}/{sourceSlug}', 'File\FileController@getImagesBySource');
    Route::get('images/{slug}', 'File\FileController@getImage');

    Route::delete('files/{slug}', 'File\FileController@deleteFile');

    /*
     * -----------
     * Customer API
     * -----------
     */
    require __DIR__ . '/customer-api.php';
});
