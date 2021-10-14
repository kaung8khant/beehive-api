<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v2', 'middleware' => ['cors', 'json.response']], function () {
    Route::group(['prefix' => 'admin'], function () {
        Route::get('test/{slug}', 'Customer\ShopController@test');
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
            Route::get('customers/{slug}/addresses', 'Admin\AddressController@index');
            Route::post('customers/{slug}/addresses', 'Admin\AddressController@store');
            Route::put('customers/{customer}/addresses/{slug}', 'Admin\AddressController@update');

            Route::get('settings', 'Admin\SettingController@index');
            Route::get('settings/{groupName}', 'Admin\SettingController@show');
            Route::put('settings/update', 'Admin\SettingController@updateSettings');

            /* Dashboard */
            Route::get('dashboard/counts', 'Dashboard\AdminDashboardController@getCountData');
            Route::get('dashboard/restaurant-orders', 'Dashboard\AdminDashboardController@getRestaurantOrders');
            Route::get('dashboard/shop-orders', 'Dashboard\AdminDashboardController@getShopOrders');
            Route::get('dashboard/order-data', 'Dashboard\AdminDashboardController@getOrderChartData');
            Route::get('dashboard/top-customers', 'Dashboard\AdminDashboardController@getTopCustomers');
            Route::get('dashboard/top-categories', 'Dashboard\AdminDashboardController@getTopShopCategories');
            /* Dashboard */

            Route::resource('roles', 'Admin\RoleController', ['except' => ['create', 'edit']]);
            Route::resource('users', 'Admin\UserController', ['except' => ['create', 'edit']]);
            Route::post('users/reset-password/{user}', 'Admin\UserController@updatePassword');
            Route::post('users/reset-password/customer/{customer}', 'Admin\UserController@updatePasswordForCustomer');
            Route::patch('users/toggle-enable/{user}', 'Admin\UserController@toggleEnable');
            Route::get('shop-users', 'Admin\UserController@getShopUsers');
            Route::post('shop-users', 'Admin\UserController@storeShopUser');
            Route::put('shop-users/{user}', 'Admin\UserController@updateShopUser');
            Route::get('restaurant-users', 'Admin\UserController@getRestaurantUsers');
            Route::post('restaurant-users', 'Admin\UserController@storeRestaurantUser');
            Route::put('restaurant-users/{user}', 'Admin\UserController@updateRestaurantUser');
            Route::get('logistics-users', 'Admin\UserController@getLogisticsUsers');
            Route::post('logistics-users', 'Admin\UserController@storeLogisticsUser');
            Route::put('logistics-users/{user}', 'Admin\UserController@updateLogisticsUser');

            Route::resource('customers', 'Admin\CustomerController', ['except' => ['create', 'edit']]);
            Route::get('customers/{customer}/orders', 'Admin\CustomerController@getOrdersByCustomer');
            Route::patch('customers/toggle-enable/{customer}', 'Admin\CustomerController@toggleEnable');

            Route::resource('drivers', 'Admin\Driver\DriverController', ['except' => ['create', 'edit']]);
            Route::patch('drivers/toggle-enable/{user}', 'Admin\Driver\DriverController@toggleEnable');

            Route::resource('collectors', 'Admin\CollectorController', ['except' => ['create', 'edit']]);
            Route::patch('collectors/toggle-enable/{user}', 'Admin\CollectorController@toggleEnable');

            // Route::resource('cities', 'Admin\CityController', ['except' => ['create', 'edit']]);
            // Route::resource('townships', 'Admin\TownshipController', ['except' => ['create', 'edit']]);
            // Route::get('cities/{city}/townships', 'Admin\TownshipController@getTownshipsByCity');

            /* Shop */
            Route::resource('shop-categories', 'Admin\ShopCategoryController', ['except' => ['create', 'edit']]);
            Route::resource('sub-categories', 'Admin\ShopSubCategoryController', ['except' => ['create', 'edit']])->parameter('sub-categories', 'shopSubCategory');
            Route::resource('shop-tags', 'Admin\ShopTagController', ['except' => ['create', 'edit']]);
            Route::resource('shops', 'Admin\ShopController', ['except' => ['create', 'edit']]);
            Route::patch('shops/toggle-enable/{shop}', 'Admin\ShopController@toggleEnable');
            Route::post('shops/status', 'Admin\ShopController@multipleStatusUpdate');
            Route::patch('shops/toggle-official/{slug}', 'Admin\ShopController@toggleOfficial');
            Route::get('shops/{shop}/customers', 'Admin\CustomerController@getCustomersByShop');
            Route::get('shop-categories/{shopCategory}/sub-categories', 'Admin\ShopSubCategoryController@getSubCategoriesByCategory');
            Route::get('shops/{shop}/shop-tags', 'Admin\ShopTagController@getTagsByShop');
            Route::get('shops/{shop}/ratings', 'Admin\ShopRatingController@getShopRatings');

            Route::resource('products', 'Admin\ProductController', ['except' => ['create', 'edit']]);
            Route::patch('products/toggle-enable/{product}', 'Admin\ProductController@toggleEnable');
            Route::post('products/status', 'Admin\ProductController@multipleStatusUpdate');
            Route::get('shops/{shop}/products', 'Admin\ProductController@getProductsByShop');
            Route::get('shop-categories/{shopCategory}/products', 'Admin\ProductController@getProductsByCategory');
            Route::post('products/multiple-delete', 'Admin\ProductController@multipleDelete');
            Route::put('products/{product}/index', 'Admin\ProductController@updateSearchIndex');

            Route::resource('product-variations', 'Admin\ProductVariationController', ['except' => ['create', 'edit']]);
            Route::get('products/{product}/product-variations', 'Admin\ProductVariationController@getProductVariationsByProduct');

            Route::resource('product-variation-values', 'Admin\ProductVariationValueController', ['except' => ['create', 'edit']]);
            Route::get('product-variations/{productVariation}/product-variation-values', 'Admin\ProductVariationValueController@getVariationValuesByVariation');

            Route::resource('brands', 'Admin\BrandController', ['except' => ['create', 'edit']]);
            Route::get('brands/{brand}/products', 'Admin\ProductController@getProductsByBrand');
            Route::get('brands/{brand}/shops', 'Admin\ShopController@getShopsByBrand');
            /* Shop */

            /* Restaurant */
            Route::resource('restaurant-categories', 'Admin\RestaurantCategoryController', ['except' => ['create', 'edit']]);
            Route::resource('restaurant-tags', 'Admin\RestaurantTagController', ['except' => ['create', 'edit']]);
            Route::resource('restaurants', 'Admin\RestaurantController', ['except' => ['create', 'edit']]);
            Route::patch('restaurants/toggle-enable/{restaurant}', 'Admin\RestaurantController@toggleEnable');
            Route::post('restaurants/status', 'Admin\RestaurantController@multipleStatusUpdate');
            Route::patch('restaurants/toggle-official/{slug}', 'Admin\RestaurantController@toggleOfficial');

            Route::put('restaurants/{restaurant}/categories/{restaurantCategory}/index', 'Admin\RestaurantCategoryController@updateSearchIndex');
            Route::put('restaurants/{restaurant}/categories/index', 'Admin\RestaurantCategoryController@updateMultipleSearchIndex');
            Route::get('restaurants/{restaurant}/categories', 'Admin\RestaurantCategoryController@getCategoriesByRestaurant');
            Route::get('restaurants/{slug}/restaurant-tags', 'Admin\RestaurantTagController@getTagsByRestaurant');

            Route::resource('menus', 'Admin\MenuController', ['except' => ['create', 'edit']]);
            Route::patch('menus/toggle-enable/{menu}', 'Admin\MenuController@toggleEnable');
            Route::post('menus/multiple-delete', 'Admin\MenuController@multipleDelete');
            Route::post('menus/status', 'Admin\MenuController@multipleStatusUpdate');

            Route::resource('menu-toppings', 'Admin\MenuToppingController', ['except' => ['create', 'edit']]);
            Route::get('menus/{menu}/menu-toppings', 'Admin\MenuToppingController@getToppingsByMenu');

            Route::get('restaurants/{restaurant}/menus', 'Admin\MenuController@getMenusByRestaurant');
            Route::get('restaurant-branches/{restaurantBranch}/menus', 'Admin\MenuController@getMenusByBranch');
            Route::get('restaurant-categories/{restaurantCategory}/menus', 'Admin\MenuController@getMenusByCategory');
            Route::get('restaurants/branches/{restaurantBranch}/menus', 'Customer\MenuController@getAvailableMenusByBranch');
            Route::get('restaurant-branches/{restaurantBranch}/menus-with-additionals', 'Admin\MenuController@getMenusByBranchWithAdditionals');

            Route::get('restaurant-branches/maps', 'Admin\RestaurantBranchController@getAll');
            Route::resource('restaurant-branches', 'Admin\RestaurantBranchController', ['except' => ['create', 'edit']]);

            Route::post('restaurant-branches/{restaurantBranch}/menus/{menu}', 'Admin\RestaurantBranchController@toggleAvailable');
            Route::post('restaurant-branches/add-available-menus/{restaurantBranch}', 'Admin\RestaurantBranchController@addAvailableMenus');
            Route::post('restaurant-branches/remove-available-menus/{slug}', 'Admin\RestaurantBranchController@removeAvailableMenus');
            Route::patch('restaurant-branches/toggle-enable/{restaurantBranch}', 'Admin\RestaurantBranchController@toggleEnable');
            Route::patch('restaurant-branches/toggle-free-delivery/{restaurantBranch}', 'Admin\RestaurantBranchController@toggleFreeDelivery');
            Route::post('restaurant-branches/status', 'Admin\RestaurantBranchController@multipleStatusUpdate');
            Route::get('restaurant-branches/{restaurantBranch}/customers', 'Admin\CustomerController@getCustomersByBranch');
            Route::get('restaurants/{restaurant}/restaurant-branches', 'Admin\RestaurantBranchController@getBranchesByRestaurant');

            Route::put('restaurant-branches/{restaurantBranch}/index', 'Admin\RestaurantBranchController@updateSearchIndex');
            Route::put('menus/{menu}/variants', 'Admin\MenuController@updateVariants');
            Route::put('menus/{menu}/index', 'Admin\MenuController@updateSearchIndex');

            // Route::resource('menu-variations', 'Admin\MenuVariationController', ['except' => ['create', 'edit']]);
            // Route::get('menus/{menu}/menu-variations', 'Admin\MenuVariationController@getVariationsByMenu');
            // Route::resource('menu-variation-values', 'Admin\MenuVariationValueController', ['except' => ['create', 'edit']]);

            // Route::get('townships/{township}/restaurant-branches', 'Admin\RestaurantBranchController@getBranchesByTownship');

            /* Restaurant */

            /* Order */
            Route::resource('restaurant-orders', 'Admin\RestaurantOrderController');
            Route::post('restaurant-orders/{restaurantOrder}/change-status', 'Admin\RestaurantOrderController@changeStatus');
            Route::resource('shop-orders', 'Admin\ShopOrderController');
            Route::post('shop-orders/{shopOrder}/change-status', 'Admin\ShopOrderController@changeStatus');
            /* Order */

            /* Promocode */
            Route::resource('promocodes', 'Admin\PromocodeController', ['except' => ['create', 'edit']]);
            Route::post('promocodes/add-rules/{promocode}', 'Admin\PromocodeController@addRules');
            Route::delete('rules/{promocodeRule:id}', 'Admin\PromocodeController@removeRule');
            Route::post('promocodes/validate/{slug}', 'Admin\PromocodeController@validateCode');

            /* Promocode */

            /* Device Token */
            Route::post('/register-device', 'Admin\UserController@registerToken');
            /* Device Token */

            Route::resource('customer-groups', 'Group\CustomerGroupController', ['except' => ['create', 'edit']]);
            Route::post('customer-groups/add/{slug}', 'Group\CustomerGroupController@addCustomersToGroup');
            Route::delete('customer-groups/remove/{slug}', 'Group\CustomerGroupController@removeCustomersFromGroup');
            Route::get('customer-groups/{slug}/customers', 'Group\CustomerGroupController@getCustomersByGroup');

            Route::post('sms/send', 'Sms\SmsController@send');
            Route::post('sms/campaigns', 'Sms\SmsController@createCampaigns');
            Route::get('sms/campaigns', 'Sms\SmsController@getSmsCampaigns');
            Route::get('sms/logs', 'Sms\SmsController@getLogs');
            Route::get('sms/logs/batch/{batchId}', 'Sms\SmsController@getLogsByBatchId');
            Route::get('sms/logs/phone/{phone}', 'Sms\SmsController@getLogsByPhone');
            Route::get('sms/logs/date/{from}/{to}', 'Sms\SmsController@getLogsByDate');

            Route::post('excels/import/{type}', 'Excel\ExportImportController@import');
            Route::get('excels/export/{type}', 'Excel\ExportImportController@export');
            Route::get('excels/export/{type}/{params}', 'Excel\ExportImportController@exportWithParams');

            Route::get('reports/restaurant-orders/all', 'Report\RestaurantOrderController@getAllOrders');
            Route::get('reports/restaurant-orders/vendor/{slug}', 'Report\RestaurantOrderController@getVendorOrders');
            Route::get('reports/restaurant-orders/branch/{slug}', 'Report\RestaurantOrderController@getBranchOrders');

            Route::get('reports/shop-orders/all', 'Report\ShopOrderController@getShopSaleInvoiceReport');
            Route::get('reports/shop-orders/shop-sales', 'Report\ShopOrderController@getShopSaleReport');
            Route::get('reports/shop-orders/vendor/{shop}/product-sales', 'Report\ShopOrderController@getShopProductSaleReport');
            Route::get('reports/shop-orders/product-sales', 'Report\ShopOrderController@getProductSaleReport');

            Route::get('reports/promocodes', 'Report\PromocodeController@getPromocodeReport');
            Route::get('reports/promocodes/{promocode}', 'Report\PromocodeController@getPromocodeUsedInvoiceReport');
            Route::get('reports/promocodes/{promocode}/customers', 'Report\PromocodeController@getPromocodeUsedCustomerReport');

            Route::get('pages', 'Admin\PageController@index');
            Route::get('pages/{page}', 'Admin\PageController@show');
            Route::patch('pages/{page}', 'Admin\PageController@update');

            /* Ads */
            Route::resource('ads', 'Admin\AdsController', ['except' => ['create', 'edit']]);
            Route::put('ads/{ads}/index', 'Admin\AdsController@updateSearchIndex');

            /* Content */
            Route::resource('contents', 'Admin\ContentController', ['except' => ['create', 'edit']]);
            Route::resource('promotions', 'Admin\PromotionController', ['except' => ['create', 'edit']]);

            // Route::post('devices', 'OneSignal\OneSignalController@registerAdminDevice');
            // Route::post('devices/send', 'OneSignal\OneSignalController@sendPushNotification');
            Route::post('devices/{playerId}', 'OneSignal\OneSignalController@registerAdminPlayerID');

            Route::post('devices/send/admins', 'OneSignal\OneSignalController@sendAdmins');
            Route::post('devices/send/vendors', 'OneSignal\OneSignalController@sendVendors');

            /* Driver */

            Route::get('profile/driver', 'Admin\Driver\DriverController@profile');

            Route::get('jobs', 'Admin\Driver\OrderDriverController@jobList');
            Route::post('jobs/{restaurantOrder}/status', 'Admin\Driver\OrderDriverController@changeStatus');
            Route::get('jobs/{restaurantOrder}', 'Admin\Driver\OrderDriverController@jobDetail');
            Route::post('jobs/assign/{slug}/drivers/{driverslug}', "Admin\Driver\OrderDriverController@manualAssignOrder");

            Route::post('attendances', 'Admin\Driver\DriverController@attendance');
            Route::get('attendances', 'Admin\Driver\DriverController@getCheckin');

            /* End Driver */

            Route::get('shop-commissions', 'Admin\CommissionController@getShopOrderCommissions');
            Route::get('shops/{shop}/commissions', 'Admin\CommissionController@getOneShopOrderCommissions');
            Route::get('restaurant-commissions', 'Admin\CommissionController@getRestaurantOrderCommissions');
            Route::get('restaurants/{restaurant}/commissions', 'Admin\CommissionController@getOneRestaurantOrderCommissions');
            Route::get('restaurant-branches/{restaurantBranch}/commissions', 'Admin\CommissionController@getRestaurantBranchOrderCommissions');
        });
    });

    Route::post('files', 'File\UploadController@upload');
    Route::get('files/{file:slug}', 'File\FileController@getFile');
    Route::get('files/{source}/{sourceSlug}', 'File\FileController@getFilesBySource');

    Route::get('images/{file:slug}', 'File\FileController@getImage');
    Route::get('images/{source}/{sourceSlug}', 'File\FileController@getImagesBySource');

    Route::delete('files/{file:slug}', 'File\FileController@deleteFile');

    Route::get('fix-slug/{table}', 'SlugFixController@fix');

    Route::get('announcements', 'Customer\ContentController@index');

    Route::get('promotions', 'Customer\PromotionController@index');

    /* KBZ Pay Notify */
    Route::post('kbz/notify', 'Payment\KbzPayController@notify');

    Route::get('test', 'SlugFixController@test');

    // Route::post('calculate-driver', 'FirebaseController@index');
});

Route::group([
    'prefix' => 'v3/admin',
    'namespace' => '\App\\Http\\Controllers\\Admin\\v3',
    'middleware' => ['cors', 'json.response', 'auth:users', 'user.enable'],
], function () {
    Route::resource('restaurant-orders', 'RestaurantOrderController', ['as' => 'admin-v3-restaurant', 'except' => ['create', 'edit']]);
    Route::post('restaurant-orders/{restaurantOrder}/status', 'RestaurantOrderController@changeStatus');
    Route::put('restaurant-orders/{restaurantOrder}/payment', 'RestaurantOrderController@updatePayment');
    Route::delete('restaurant-orders/{restaurantOrder}/restaurant-order-items/{restaurantOrderItem}/cancel', 'RestaurantOrderController@cancelOrderItem');

    Route::resource('shop-orders', 'ShopOrderController', ['as' => 'admin-v3-shop', 'except' => ['create', 'edit']]);
    Route::post('shop-orders/{shopOrder}/status', 'ShopOrderController@changeStatus');
    Route::put('shop-orders/{shopOrder}/payment', 'ShopOrderController@updatePayment');
    Route::delete('shop-orders/{shopOrder}/shop-order-items/{shopOrderItem}/cancel', 'ShopOrderController@cancelOrderItem');

    Route::resource('menu-options', 'MenuOptionController', ['as' => 'admin-v3-menu-option', 'except' => ['create', 'edit']]);
    Route::get('menus/{menu}/menu-options', 'MenuOptionController@index');

    Route::resource('menu-option-items', 'MenuOptionItemController', ['as' => 'admin-v3-menu-option-item', 'except' => ['create', 'edit']]);
    Route::get('menu-options/{menuOption}/items', 'MenuOptionItemController@index');
});

Route::group(['prefix' => 'v3', 'middleware' => ['cors', 'json.response']], function () {
    /* Restaurant Cart */
    Route::post('carts', 'Cart\CartController@viewCart');
    Route::post('restaurants/carts/menus/{menu}', 'Cart\RestaurantCartController@store');
    Route::put('restaurants/carts/menus/{menu}', 'Cart\RestaurantCartController@updateQuantity');

    Route::delete('restaurants/carts/menus/{menu}', 'Cart\RestaurantCartController@delete');
    Route::delete('restaurants/carts', 'Cart\RestaurantCartController@deleteCart');

    Route::post('restaurants/carts/promocode', 'Cart\RestaurantCartController@applyPromocode');
    Route::delete('restaurants/carts/promocode', 'Cart\RestaurantCartController@removePromocode');

    Route::put('restaurants/carts/address', 'Cart\RestaurantCartController@updateAddress');
    Route::post('restaurants/carts/checkout', 'Cart\RestaurantCartController@checkout');
    /* Restaurant Cart */

    /* Shop Cart */
    Route::post('shops/carts/products/{product}', 'Cart\ShopCartController@store');
    Route::put('shops/carts/products/{product}', 'Cart\ShopCartController@updateQuantity');

    Route::delete('shops/carts/products/{product}', 'Cart\ShopCartController@delete');
    Route::delete('shops/carts', 'Cart\ShopCartController@deleteCart');

    Route::post('shops/carts/promocode', 'Cart\ShopCartController@applyPromocode');
    Route::delete('shops/carts/promocode', 'Cart\ShopCartController@removePromocode');

    Route::put('shops/carts/address', 'Cart\ShopCartController@updateAddress');
    Route::post('shops/carts/checkout', 'Cart\ShopCartController@checkout');
    /* Shop Cart */

    Route::get('versions', 'SettingsController@getAppVersions');

    Route::get('restaurants/invoice/{slug}/generate', 'Pdf\RestaurantInvoiceController@generateInvoice');
    Route::get('shops/invoice/{slug}/generate', 'Pdf\ShopInvoiceController@generateInvoice');
});

/*
 * -----------
 * Vendor API
 * -----------
 */
require __DIR__ . '/vendor-api.php';

/*
 * -----------
 * Customer API
 * -----------
 */
require __DIR__ . '/customer-api.php';
