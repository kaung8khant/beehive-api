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
            Route::post('customers/{slug}/addresses', 'AddressController@store');

            Route::get('settings', 'SettingController@index');
            Route::get('settings/{groupName}', 'SettingController@show');
            Route::put('settings/update', 'SettingController@updateSettings');

            /* Dashboard */
            Route::get('dashboard/counts', 'Dashboard\AdminDashboardController@getCountData');
            Route::get('dashboard/restaurant-orders', 'Dashboard\AdminDashboardController@getRestaurantOrders');
            Route::get('dashboard/shop-orders', 'Dashboard\AdminDashboardController@getShopOrders');
            Route::get('dashboard/order-data', 'Dashboard\AdminDashboardController@getOrderChartData');
            /* Dashboard */

            Route::resource('roles', 'RoleController', ['except' => ['create', 'edit']]);
            Route::resource('users', 'UserController', ['except' => ['create', 'edit']]);
            Route::post('users/reset-password/{user}', 'UserController@updatePassword');
            Route::post('users/reset-password/customer/{customer}', 'UserController@updatePasswordForCustomer');
            Route::patch('users/toggle-enable/{user}', 'UserController@toggleEnable');
            Route::get('shop-users', 'UserController@getShopUsers');
            Route::post('shop-users', 'UserController@storeShopUser');
            Route::put('shop-users/{user}', 'UserController@updateShopUser');
            Route::get('restaurant-users', 'UserController@getRestaurantUsers');
            Route::post('restaurant-users', 'UserController@storeRestaurantUser');
            Route::put('restaurant-users/{user}', 'UserController@updateRestaurantUser');
            Route::get('logistics-users', 'UserController@getLogisticsUsers');
            Route::post('logistics-users', 'UserController@storeLogisticsUser');
            Route::put('logistics-users/{user}', 'UserController@updateLogisticsUser');

            Route::resource('customers', 'CustomerController', ['except' => ['create', 'edit']]);
            Route::patch('customers/toggle-enable/{customer}', 'CustomerController@toggleEnable');
            Route::post('customers/import', 'CustomerController@import');

            Route::resource('drivers', 'DriverController', ['except' => ['create', 'edit']]);
            Route::patch('drivers/toggle-enable/{user}', 'DriverController@toggleEnable');

            Route::resource('collectors', 'CollectorController', ['except' => ['create', 'edit']]);
            Route::patch('collectors/toggle-enable/{user}', 'CollectorController@toggleEnable');

            Route::resource('cities', 'CityController', ['except' => ['create', 'edit']]);
            Route::resource('townships', 'TownshipController', ['except' => ['create', 'edit']]);
            Route::get('cities/{city}/townships', 'TownshipController@getTownshipsByCity');

            /* Shop */
            Route::resource('shop-categories', 'ShopCategoryController', ['except' => ['create', 'edit']]);
            Route::post('shop-categories/import', 'ShopCategoryController@import');
            Route::resource('sub-categories', 'ShopSubCategoryController', ['except' => ['create', 'edit']]);
            Route::post('sub-categories/import', 'ShopSubCategoryController@import');
            Route::resource('shop-tags', 'ShopTagController', ['except' => ['create', 'edit']]);
            Route::post('shop-tags/import', 'ShopTagController@import');
            Route::resource('shops', 'ShopController', ['except' => ['create', 'edit']]);
            Route::patch('shops/toggle-enable/{shop}', 'ShopController@toggleEnable');
            Route::post('shops/status', 'ShopController@multipleStatusUpdate');
            Route::patch('shops/toggle-official/{slug}', 'ShopController@toggleOfficial');
            Route::post('shops/import', 'ShopController@import');
            Route::get('shops/{slug}/customers', 'ShopController@getCustomersByShop');
            Route::get('shop-categories/{shopCategory}/sub-categories', 'ShopSubCategoryController@getSubCategoriesByCategory');
            Route::get('shops/{slug}/shop-tags', 'ShopTagController@getTagsByShop');
            Route::get('shops/{shop}/ratings', 'ShopRatingController@getShopRatings');

            Route::resource('products', 'ProductController', ['except' => ['create', 'edit']]);
            Route::post('products/import', 'ProductController@import');
            Route::patch('products/toggle-enable/{product}', 'ProductController@toggleEnable');
            Route::post('products/status', 'ProductController@multipleStatusUpdate');
            Route::get('shops/{shop}/products', 'ProductController@getProductsByShop');
            Route::get('shop-categories/{shopCategory}/products', 'ProductController@getProductsByCategory');
            Route::post('products/multiple-delete', 'ProductController@multipleDelete');

            Route::resource('product-variations', 'ProductVariationController', ['except' => ['create', 'edit']]);
            Route::get('products/{product}/product-variations', 'ProductVariationController@getProductVariationsByProduct');

            Route::resource('product-variation-values', 'ProductVariationValueController', ['except' => ['create', 'edit']]);
            Route::get('product-variations/{productVariation}/product-variation-values', 'ProductVariationValueController@getVariationValuesByVariation');

            Route::resource('brands', 'BrandController', ['except' => ['create', 'edit']]);
            Route::get('brands/{brand}/products', 'ProductController@getProductsByBrand');
            Route::get('brands/{slug}/shops', 'ShopController@getShopsByBrand');
            Route::post('brands/import', 'BrandController@import');
            /* Shop */

            /* Restaurant */
            Route::resource('restaurant-categories', 'RestaurantCategoryController', ['except' => ['create', 'edit']]);
            Route::post('restaurant-categories/import', 'RestaurantCategoryController@import');
            Route::resource('restaurant-tags', 'RestaurantTagController', ['except' => ['create', 'edit']]);
            Route::post('restaurant-tags/import', 'RestaurantTagController@import');
            Route::resource('restaurants', 'RestaurantController', ['except' => ['create', 'edit']]);
            Route::post('restaurants/import', 'RestaurantController@import');
            Route::patch('restaurants/toggle-enable/{restaurant}', 'RestaurantController@toggleEnable');
            Route::post('restaurants/status', 'RestaurantController@multipleStatusUpdate');
            Route::patch('restaurants/toggle-official/{slug}', 'RestaurantController@toggleOfficial');
            Route::get('restaurants/{slug}/restaurant-tags', 'RestaurantTagController@getTagsByRestaurant');
            Route::resource('menus', 'MenuController', ['except' => ['create', 'edit']]);
            Route::post('menus/import', 'MenuController@import');
            Route::resource('menu-variations', 'MenuVariationController', ['except' => ['create', 'edit']]);
            Route::resource('menu-variation-values', 'MenuVariationValueController', ['except' => ['create', 'edit']]);
            Route::resource('menu-toppings', 'MenuToppingController', ['except' => ['create', 'edit']]);
            Route::patch('menus/toggle-enable/{menu}', 'MenuController@toggleEnable');
            Route::post('menus/status', 'MenuController@multipleStatusUpdate');
            Route::post('menus/multiple-delete', 'MenuController@multipleDelete');
            Route::get('restaurants/{restaurant}/menus', 'MenuController@getMenusByRestaurant');
            Route::get('menus/{menu}/menu-variations', 'MenuVariationController@getVariationsByMenu');
            Route::get('menus/{menu}/menu-toppings', 'MenuToppingController@getToppingsByMenu');
            Route::resource('restaurant-branches', 'RestaurantBranchController', ['except' => ['create', 'edit']]);
            Route::get('restaurant-branches/{restaurantBranch}/menus', 'MenuController@getMenusByBranch');
            Route::get('restaurant-categories/{restaurantCategory}/menus', 'MenuController@getMenusByCategory');
            Route::get('restaurant-branches/{restaurantBranch}/menus-with-additionals', 'MenuController@getMenusByBranchWithAdditionals');
            Route::post('restaurant-branches/{restaurantBranch}/menus/{menu}', 'RestaurantBranchController@toggleAvailable');
            Route::post('restaurant-branches/add-available-menus/{restaurantBranch}', 'RestaurantBranchController@addAvailableMenus');
            Route::post('restaurant-branches/remove-available-menus/{slug}', 'RestaurantBranchController@removeAvailableMenus');
            Route::patch('restaurant-branches/toggle-enable/{restaurantBranch}', 'RestaurantBranchController@toggleEnable');
            Route::post('restaurant-branches/status', 'RestaurantBranchController@multipleStatusUpdate');
            Route::post('restaurant-branches/import', 'RestaurantBranchController@import');
            Route::get('restaurant-branches/{restaurantBranch}/customers', 'RestaurantBranchController@getRestaurantBranchByCustomers');
            Route::get('restaurants/{restaurant}/restaurant-branches', 'RestaurantBranchController@getBranchesByRestaurant');
            Route::get('townships/{township}/restaurant-branches', 'RestaurantBranchController@getBranchesByTownship');

            /* Restaurant */

            /* Order */
            Route::resource('restaurant-orders', 'RestaurantOrderController');
            Route::post('restaurant-orders/{restaurantOrder}/change-status', 'RestaurantOrderController@changeStatus');
            Route::resource('shop-orders', 'ShopOrderController');
            Route::post('shop-orders/{shopOrder}/change-status', 'ShopOrderController@changeStatus');
            /* Order */

            /* Promocode */
            Route::resource('promocodes', 'PromocodeController', ['except' => ['create', 'edit']]);
            Route::post('promocodes/add-rules/{promocode}', 'PromocodeController@addRules');
            Route::delete('rules/{promocodeRule:id}', 'PromocodeController@removeRule');
            Route::post('promocodes/validate/{slug}', 'PromocodeController@validateCode');
            Route::get('promocodes/{promocode}/customers', 'CustomerController@getPromocodeUsedCustomers');

            /* Promocode */

            /* Device Token */
            Route::post('/register-device', 'UserController@registerToken');
            /* Device Token */

            Route::resource('customer-groups', 'Group\CustomerGroupController', ['except' => ['create', 'edit']]);
            Route::post('customer-groups/add/{slug}', 'Group\CustomerGroupController@addCustomersToGroup');
            Route::delete('customer-groups/remove/{slug}', 'Group\CustomerGroupController@removeCustomersFromGroup');
            Route::get('customer-groups/{slug}/customers', 'Group\CustomerGroupController@getCustomersByGroup');
            Route::post('customer-groups/import', 'Group\CustomerGroupController@import');
            Route::post('customer-groups/{slug}/customers/import', 'Group\CustomerGroupController@importCustomerToGroup');

            Route::post('sms/send', 'Sms\SmsController@send');
            Route::get('sms/logs', 'Sms\SmsController@getLogs');
            Route::get('sms/logs/batch/{batchId}', 'Sms\SmsController@getLogsByBatchId');
            Route::get('sms/logs/phone/{phone}', 'Sms\SmsController@getLogsByPhone');
            Route::get('sms/logs/date/{from}/{to}', 'Sms\SmsController@getLogsByDate');

            Route::post('excels/import/{type}', 'Excel\ExportImportController@import');
            Route::get('excels/export/{type}', 'Excel\ExportImportController@export');

            Route::get('pages', 'PageController@index');
            Route::get('pages/{page}', 'PageController@show');
            Route::patch('pages/{page}', 'PageController@update');

            /*Ads */
            Route::resource('ads', 'AdsController', ['except' => ['create', 'edit']]);

            /*Content */
            Route::resource('contents', 'ContentController', ['except' => ['create', 'edit']]);
        });
    });

    Route::post('files', 'File\UploadController@upload');
    Route::get('files/{file:slug}', 'File\FileController@getFile');
    Route::get('files/{source}/{sourceSlug}', 'File\FileController@getFilesBySource');

    Route::get('images/{file:slug}', 'File\FileController@getImage');
    Route::get('images/{source}/{sourceSlug}', 'File\FileController@getImagesBySource');

    Route::delete('files/{file:slug}', 'File\FileController@deleteFile');

    Route::get('fix-slug/{table}', 'SlugFixController@fix');

    Route::get('contents', 'ContentController@index');

    /*
     * -----------
     * Customer API
     * -----------
     */
    require __DIR__ . '/customer-api.php';
    require __DIR__ . '/vendor-api.php';
});
