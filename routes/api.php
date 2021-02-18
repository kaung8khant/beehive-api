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
            Route::get('user-detail', 'Auth\UserAuthController@getAuthenticatedUser');
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

            Route::resource('restaurant-categories', 'RestaurantCategoryController');
            Route::resource('shop-categories', 'ShopCategoryController');
            Route::resource('sub-categories', 'SubCategoryController');
            Route::get('shop-categories/{slug}/sub-categories', 'SubCategoryController@getSubCategoriesByCategory');

            Route::resource('restaurants', 'RestaurantController');
            Route::resource('restaurant-tags', 'RestaurantTagController');
            Route::get('restaurants/{slug}/restaurant-tags', 'RestaurantTagController@getTagsByRestaurant');
            Route::get('restaurants/{slug}/restaurant-categories', 'RestaurantCategoryController@getCategoriesByRestaurant');

            Route::resource('shop-tags', 'ShopTagController');
            Route::resource('shops', 'ShopController');
            Route::get('shops/{slug}/shop-tags', 'ShopTagController@getTagsByShop');
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

            Route::get('profile', 'ProfileController@index');
            Route::put('profile/update', 'ProfileController@update_profile');

            Route::resource('orders', 'OrderController');
            Route::resource('order-contacts', 'OrderContactController');
            Route::resource('order-status', 'OrderStatusController');
            Route::get('orders/{status}/order-status', 'OrderStatusController@getStatusByOrder')->name('getStatusByOrder');
            Route::resource('order-items', 'OrderItemController');

            Route::resource('ratings', 'RatingController');
            Route::get('orders/{receiverType}/ratings', 'RatingController@getReceiverTypeByOrder')->name('getReceiverTypeByOrder');
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
