<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v2', 'middleware' => ['cors', 'json.response']], function () {
    Route::group(['prefix' => 'admin'], function () {
        Route::post('login', 'Auth\UserAuthController@login');
        Route::post('forgot-password', 'Auth\OtpController@forgotPassword');
        Route::post('reset-password', 'Auth\UserAuthController@resetPassword');

        Route::middleware(['auth:users', 'user.enable'])->group(function () {
            Route::get('profile', 'Auth\UserAuthController@getProfile');
            Route::put('profile/update', 'Auth\UserAuthController@updateProfile');
            Route::patch('password/update', 'Auth\UserAuthController@updatePassword');
            Route::post('refresh-token', 'Auth\UserAuthController@refreshToken');
            Route::post('logout', 'Auth\UserAuthController@logout');

            /* Address */
            Route::get('customers/{slug}/addresses', 'AddressController@index');

            Route::get('settings', 'SettingController@index');
            Route::get('settings/{key}', 'SettingController@show');
            Route::put('settings/update', 'SettingController@updateSetting');

            /* Dashboard */
            Route::get('dashboard/counts', 'Dashboard\AdminDashboardController@getCountData');
            Route::get('dashboard/restaurant-orders', 'Dashboard\AdminDashboardController@getRestaurantOrders');
            Route::get('dashboard/shop-orders', 'Dashboard\AdminDashboardController@getShopOrders');
            Route::get('dashboard/order-data', 'Dashboard\AdminDashboardController@getOrderChartData');
            /* Dashboard */

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
            Route::post('shop-categories/import', 'ShopCategoryController@import');
            Route::resource('sub-categories', 'ShopSubCategoryController');
            Route::post('sub-categories/import', 'ShopSubCategoryController@import');
            Route::resource('shop-tags', 'ShopTagController');
            Route::post('shop-tags/import', 'ShopTagController@import');
            Route::resource('shops', 'ShopController');
            Route::patch('shops/toggle-enable/{slug}', 'ShopController@toggleEnable');
            Route::patch('shops/toggle-official/{slug}', 'ShopController@toggleOfficial');
            Route::post('shops/add-shop-categories/{slug}', 'ShopController@addShopCategories');
            Route::post('shops/remove-shop-categories/{slug}', 'ShopController@removeShopCategories');
            Route::post('shops/{slug}/createAvailableCategory', 'ShopController@createAvailableCategory');
            Route::post('shops/import', 'ShopController@import');
            Route::get('shop-categories/{slug}/sub-categories', 'ShopSubCategoryController@getSubCategoriesByCategory');
            Route::get('shops/{slug}/shop-categories', 'ShopCategoryController@getCategoriesByShop');
            Route::get('shops/{slug}/shop-tags', 'ShopTagController@getTagsByShop');

            Route::resource('products', 'ProductController');
            Route::post('products/import', 'ProductController@import');
            Route::patch('products/toggle-enable/{slug}', 'ProductController@toggleEnable');
            Route::get('shops/{slug}/products', 'ProductController@getProductsByShop');

            Route::resource('product-variations', 'ProductVariationController');
            Route::get('products/{slug}/product-variations', 'ProductVariationController@getProductVariationsByProduct');

            Route::resource('product-variation-values', 'ProductVariationValueController');
            Route::get('product-variations/{slug}/product-variation-values', 'ProductVariationValueController@getProductVariationValuesByProductVariation');

            Route::resource('brands', 'BrandController');
            Route::get('brands/{slug}/products', 'ProductController@getProductsByBrand');
            Route::post('brands/import', 'BrandController@import');
            /* Shop */

            /* Restaurant */
            Route::resource('restaurant-categories', 'RestaurantCategoryController');
            Route::post('restaurant-categories/import', 'RestaurantCategoryController@import');
            Route::resource('restaurant-tags', 'RestaurantTagController');
            Route::post('restaurant-tags/import', 'RestaurantTagController@import');
            Route::resource('restaurants', 'RestaurantController');
            Route::post('restaurants/import', 'RestaurantController@import');
            Route::patch('restaurants/toggle-enable/{slug}', 'RestaurantController@toggleEnable');
            Route::patch('restaurants/toggle-official/{slug}', 'RestaurantController@toggleOfficial');
            Route::post('restaurants/add-restaurant-categories/{slug}', 'RestaurantController@addRestaurantCategories');
            Route::post('restaurants/remove-restaurant-categories/{slug}', 'RestaurantController@removeRestaurantCategories');
            Route::post('restaurants/create-restaurant-categories/{slug}', 'RestaurantController@createAvailableRestaurantCategories');
            Route::get('restaurants/{slug}/restaurant-categories', 'RestaurantCategoryController@getCategoriesByRestaurant');
            Route::get('restaurants/{slug}/restaurant-tags', 'RestaurantTagController@getTagsByRestaurant');
            Route::resource('menus', 'MenuController');
            Route::post('menus/import', 'MenuController@import');
            Route::resource('menu-variations', 'MenuVariationController');
            Route::resource('menu-variation-values', 'MenuVariationValueController');
            Route::resource('menu-toppings', 'MenuToppingController');
            Route::patch('menus/toggle-enable/{slug}', 'MenuController@toggleEnable');
            Route::get('restaurants/{slug}/menus', 'MenuController@getMenusByRestaurant');
            Route::get('menus/{slug}/menu-variations', 'MenuVariationController@getVariationsByMenu');
            Route::get('menus/{slug}/menu-toppings', 'MenuToppingController@getToppingsByMenu');
            Route::resource('restaurant-branches', 'RestaurantBranchController');
            Route::get('restaurant-branches/{slug}/menus', 'MenuController@getMenusByBranch');
            Route::get('restaurant-branches/{slug}/menus-with-additionals', 'MenuController@getMenusByBranchWithAdditionals');
            Route::post('restaurant-branches/{restaurantBranchSlug}/menus/{slug}', 'RestaurantBranchController@toggleAvailable');
            Route::post('restaurant-branches/add-available-menus/{slug}', 'RestaurantBranchController@addAvailableMenus');
            Route::post('restaurant-branches/remove-available-menus/{slug}', 'RestaurantBranchController@removeAvailableMenus');
            Route::patch('restaurant-branches/toggle-enable/{slug}', 'RestaurantBranchController@toggleEnable');
            Route::post('restaurant-branches/import', 'RestaurantBranchController@import');
            Route::get('restaurants/{slug}/restaurant-branches', 'RestaurantBranchController@getBranchesByRestaurant');
            Route::get('townships/{slug}/restaurant-branches', 'RestaurantBranchController@getBranchesByTownship');
            /* Restaurant */

            /* Order */
            Route::resource('restaurant-orders', 'RestaurantOrderController');
            Route::post('restaurant-orders/{slug}/change-status', 'RestaurantOrderController@changeStatus');
            Route::resource('shop-orders', 'ShopOrderController');
            Route::post('shop-orders/{slug}/change-status', 'ShopOrderController@changeStatus');
            /* Order */

            /* Promocode */
            Route::resource('promocodes', 'PromocodeController');
            Route::post('promocodes/add-rules/{slug}', 'PromocodeController@addRules');
            Route::delete('rules/{id}', 'PromocodeController@removeRule');
            /* Promocode */

            /* Device Token */
            Route::post('/register-device', 'UserController@registerToken');
            /* Device Token */
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
