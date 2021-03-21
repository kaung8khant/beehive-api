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
            Route::patch('password/update', 'Auth\UserAuthController@updatePassword');
            Route::post('refresh-token', 'Auth\UserAuthController@refreshToken');
            Route::post('logout', 'Auth\UserAuthController@logout');

            Route::get('settings', 'SettingController@index');
            Route::get('settings/{key}', 'SettingController@show');
            Route::put('settings/update', 'SettingController@updateSetting');

            Route::resource('roles', 'RoleController');
            Route::resource('users', 'UserController');
            Route::patch('users/toggle-enable/{slug}', 'UserController@toggleEnable');
            Route::get('shop-users', 'UserController@getShopUsers');
            Route::post('shop-users', 'UserController@storeShopUser');
            Route::put('shop-users/{slug}', 'UserController@updateShopUser');
            Route::get('restaurant-users', 'UserController@getRestaurantUsers');
            Route::post('restaurant-users', 'UserController@storeRestaurantUser');
            Route::put('restaurant-users/{slug}', 'UserController@updateRestaurantUser');

            Route::resource('customers', 'CustomerController');
            Route::patch('customers/toggle-enable/{slug}', 'CustomerController@toggleEnable');

            Route::resource('drivers', 'DriverController');
            Route::patch('drivers/toggle-enable/{slug}', 'DriverController@toggleEnable');

            Route::resource('collectors', 'CollectorController');
            Route::patch('collectors/toggle-enable/{slug}', 'CollectorController@toggleEnable');

            Route::resource('cities', 'CityController');
            Route::resource('townships', 'TownshipController');
            Route::get('cities/{slug}/townships', 'TownshipController@getTownshipsByCity');

            /* Shop */
            Route::resource('shop-categories', 'ShopCategoryController');
            Route::resource('sub-categories', 'ShopSubCategoryController');
            Route::resource('shop-tags', 'ShopTagController');
            Route::resource('shops', 'ShopController');
            Route::patch('shops/toggle-enable/{slug}', 'ShopController@toggleEnable');
            Route::patch('shops/toggle-official/{slug}', 'ShopController@toggleOfficial');
            Route::post('shops/add-shop-categories/{slug}', 'ShopController@addShopCategories');
            Route::post('shops/remove-shop-categories/{slug}', 'ShopController@removeShopCategories');
            Route::get('shop-categories/{slug}/sub-categories', 'ShopSubCategoryController@getSubCategoriesByCategory');
            Route::get('shops/{slug}/shop-categories', 'ShopCategoryController@getCategoriesByShop');
            Route::get('shops/{slug}/shop-tags', 'ShopTagController@getTagsByShop');

            Route::resource('products', 'ProductController');
            Route::patch('products/toggle-enable/{slug}', 'ProductController@toggleEnable');
            Route::get('shops/{slug}/products', 'ProductController@getProductsByShop');

            Route::resource('product-variations', 'ProductVariationController');
            Route::get('products/{slug}/product-variations', 'ProductVariationController@getProductVariationsByProduct');

            Route::resource('product-variation-values', 'ProductVariationValueController');
            Route::get('product-variations/{slug}/product-variation-values', 'ProductVariationValueController@getProductVariationValuesByProductVariation');

            Route::resource('brands', 'BrandController');
            Route::get('brands/{slug}/products', 'ProductController@getProductsByBrand');


            /* Shop */

            /* Restaurant */
            Route::resource('restaurant-categories', 'RestaurantCategoryController');
            Route::resource('restaurant-tags', 'RestaurantTagController');
            Route::resource('restaurants', 'RestaurantController');
            Route::patch('restaurants/toggle-enable/{slug}', 'RestaurantController@toggleEnable');
            Route::patch('restaurants/toggle-official/{slug}', 'RestaurantController@toggleOfficial');
            Route::post('restaurants/add-restaurant-categories/{slug}', 'RestaurantController@addRestaurantCategories');
            Route::post('restaurants/remove-restaurant-categories/{slug}', 'RestaurantController@removeRestaurantCategories');
            Route::get('restaurants/{slug}/restaurant-categories', 'RestaurantCategoryController@getCategoriesByRestaurant');
            Route::get('restaurants/{slug}/restaurant-tags', 'RestaurantTagController@getTagsByRestaurant');
            Route::resource('menus', 'MenuController');
            Route::resource('menu-variations', 'MenuVariationController');
            Route::resource('menu-variation-values', 'MenuVariationValueController');
            Route::resource('menu-toppings', 'MenuToppingController');
            Route::patch('menus/toggle-enable/{slug}', 'MenuController@toggleEnable');
            Route::get('restaurants/{slug}/menus', 'MenuController@getMenusByRestaurant');
            Route::get('menus/{slug}/menu-variations', 'MenuVariationController@getVariationsByMenu');
            Route::get('menus/{slug}/menu-toppings', 'MenuToppingController@getToppingsByMenu');
            Route::resource('restaurant-branches', 'RestaurantBranchController');
            Route::get('restaurant-branches/{slug}/menus', 'MenuController@getMenusByBranch');
            Route::get('restaurant-branches/{slug}/available-menus', 'MenuController@getAvailableMenusByBranch');
            Route::post('restaurant-branches/{restaurantBranchSlug}/menus/{slug}', 'RestaurantBranchController@toggleAvailable');
            Route::post('restaurant-branches/add-available-menus/{slug}', 'RestaurantBranchController@addAvailableMenus');
            Route::post('restaurant-branches/remove-available-menus/{slug}', 'RestaurantBranchController@removeAvailableMenus');
            Route::patch('restaurant-branches/toggle-enable/{slug}', 'RestaurantBranchController@toggleEnable');
            Route::get('restaurants/{slug}/restaurant-branches', 'RestaurantBranchController@getBranchesByRestaurant');
            Route::get('townships/{slug}/restaurant-branches', 'RestaurantBranchController@getBranchesByTownship');
            /* Restaurant */

            /* Order */
            Route::resource('restaurant-orders', 'RestaurantOrderController');
            Route::resource('shop-orders', 'ShopOrderController');
            Route::resource('orders', 'OrderController');
            Route::get('customers/{slug}/orders', 'OrderController@getOrdersByCustomer');

            Route::get('orders/{slug}/items', 'OrderItemController@index');
            Route::post('orders/{slug}/items', 'OrderItemController@store');
            Route::get('orders/{slug}/items/{id}', 'OrderItemController@show');
            Route::put('orders/{slug}/items/{id}', 'OrderItemController@update');
            Route::delete('orders/{slug}/items/{id}', 'OrderItemController@destroy');

            Route::post('orders/{slug}/status', 'OrderStatusController@store');
            Route::get('orders/{slug}/status/all', 'OrderStatusController@index');
            Route::get('orders/{slug}/status/latest', 'OrderStatusController@getLatestOrderStatus');

            Route::get('orders/{slug}/contact', 'OrderContactController@index');
            Route::put('orders/{slug}/contact', 'OrderContactController@update');

            Route::resource('ratings', 'RatingController');
            Route::get('orders/{receiverType}/ratings', 'RatingController@getReceiverTypeByOrder');
            /* Order */

            /* Promocode */
            Route::resource('promocodes', 'PromocodeController');
            Route::post('promocodes/add-rules/{slug}', 'PromocodeController@addRules');
            Route::delete('rules/{id}', 'PromocodeController@removeRule');
            /* Promocode */
        });
    });

    Route::post('files', 'File\UploadController@upload');
    Route::get('files/{slug}', 'File\FileController@getFile');
    Route::get('files/{source}/{sourceSlug}', 'File\FileController@getFilesBySource');

    Route::get('images/{slug}', 'File\FileController@getImage');
    Route::get('images/{source}/{sourceSlug}', 'File\FileController@getImagesBySource');

    Route::delete('files/{slug}', 'File\FileController@deleteFile');

    /*
     * -----------
     * Customer API
     * -----------
     */
    require __DIR__ . '/customer-api.php';
    require __DIR__ . '/vendor-api.php';
});
