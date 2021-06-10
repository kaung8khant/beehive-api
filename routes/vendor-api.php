<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v2/vendor', 'middleware' => ['cors', 'json.response']], function () {
    Route::post('login', 'Auth\VendorAuthController@login');
    Route::post('register', 'Auth\VendorAuthController@register');

    Route::post('forgot-password', 'Auth\OtpController@forgotPassword');
    Route::post('reset-password', 'Auth\VendorAuthController@resetPassword');

    Route::middleware(['auth:vendors', 'user.enable'])->group(function () {
        Route::get('profile', 'Auth\VendorAuthController@getProfile');
        Route::put('profile/update', 'Auth\VendorAuthController@updateProfile');
        Route::patch('password/update', 'Auth\VendorAuthController@updatePassword');
        Route::post('refresh-token', 'Auth\VendorAuthController@refreshToken');
        Route::post('logout', 'Auth\VendorAuthController@logout');

        /* Dashboard */
        Route::get('dashboard/order-data', 'Dashboard\VendorDashboardController@getOrderData');
        Route::get('dashboard/daywise-orders', 'Dashboard\VendorDashboardController@getDaywiseOrders');
        Route::get('dashboard/total-earnings', 'Dashboard\VendorDashboardController@getTotalEarnings');
        Route::get('dashboard/top-sellings', 'Dashboard\VendorDashboardController@getTopSellings');
        Route::get('dashboard/recent-orders', 'Dashboard\VendorDashboardController@getRecentOrders');
        /* Dashboard */

        /* restaurant */
        /* restaurant categories */
        Route::get('restaurants/{slug}/restaurant-categories', 'Admin\RestaurantCategoryController@getCategoriesByRestaurant');
        Route::post('restaurants/add-restaurant-categories/{slug}', 'Admin\RestaurantController@addRestaurantCategories');
        Route::post('restaurants/remove-restaurant-categories/{slug}', 'Admin\RestaurantController@removeRestaurantCategories');
        Route::put('restaurant-branches/{restaurantBranch}/update', 'Admin\RestaurantBranchController@updateWithTagsAndCategories');
        Route::get('restaurant-categories', 'Admin\RestaurantCategoryController@index');
        Route::get('restaurant-tags', 'Admin\RestaurantTagController@index');

        /* menus */
        Route::get('restaurant-branches/{restaurantBranch}/menus', 'Admin\MenuController@getMenusByBranch');
        Route::get('restaurant-branches/{restaurantBranch}/menus-with-additionals', 'Admin\MenuController@getMenusByBranchWithAdditionals');
        Route::post('menus/status', 'Admin\MenuController@multipleStatusUpdate');
        Route::patch('menus/toggle-enable/{slug}', 'Admin\MenuController@toggleEnable');
        Route::post('menus/multiple-delete', 'Admin\MenuController@multipleDelete');
        Route::resource('menus', 'Admin\MenuController', ['as' => 'vendor', 'except' => ['create', 'edit']]);

        Route::post('restaurant-branches/{restaurantBranch}/menus/{menu}', 'Admin\RestaurantBranchController@toggleAvailable');

        Route::get('menu-variations/{slug}', 'Admin\MenuVariationController@show');
        Route::post('menu-variations', 'Admin\MenuVariationController@store');
        Route::put('menu-variations/{slug}', 'Admin\MenuVariationController@update');
        Route::delete('menu-variations/{slug}', 'Admin\MenuVariationController@destroy');

        Route::get('menu-variation-values/{slug}', 'Admin\MenuVariationValueController@show');
        Route::post('menu-variation-values', 'Admin\MenuVariationValueController@store');
        Route::put('menu-variation-values/{slug}', 'Admin\MenuVariationValueController@update');
        Route::delete('menu-variation-values/{slug}', 'Admin\MenuVariationValueController@destroy');

        Route::get('menu-toppings/{slug}', 'Admin\MenuToppingController@show');
        Route::post('menu-toppings', 'Admin\MenuToppingController@store');
        Route::put('menu-toppings/{slug}', 'Admin\MenuToppingController@update');
        Route::delete('menu-toppings/{slug}', 'Admin\MenuToppingController@destroy');

        Route::get('restaurant-branches/{restaurantBranch}/orders', 'Admin\RestaurantOrderController@getBranchOrders');
        Route::post('restaurant-orders/{restaurantOrder}/change-status', 'Admin\RestaurantOrderController@changeStatus');
        Route::resource('restaurant-orders', 'Admin\RestaurantOrderController', ['as' => 'vendor']);
        Route::get('restaurant-branches/{restaurantBranch}/customers', 'Admin\RestaurantBranchController@getRestaurantBranchByCustomers');
        /* restaurant */

        /* shop */
        /* shop categories */
        Route::resource('shops', 'Admin\ShopController', ['as' => 'vendor']);
        Route::get('shop-tags', 'Admin\ShopTagController@index');
        Route::get('shop-categories', 'Admin\ShopCategoryController@index');
        Route::get('shops/{slug}/shop-categories', 'Admin\ShopCategoryController@getCategoriesByShop');
        Route::post('shops/add-shop-categories/{slug}', 'Admin\ShopController@addShopCategories');
        Route::post('shops/remove-shop-categories/{slug}', 'Admin\ShopController@removeShopCategories');
        Route::get('shop-categories/{shopCategory}/sub-categories', 'Admin\ShopSubCategoryController@getSubCategoriesByCategory');

        Route::resource('shop-orders', 'Admin\ShopOrderController', ['as' => 'vendor']);
        Route::get('shops/{shop}/shop-orders', 'Admin\ShopOrderController@getShopOrders');
        Route::post('shop-orders/{shopOrder}/change-status', 'Admin\ShopOrderController@changeStatus');
        Route::get('shops/{slug}/customers', 'Admin\ShopController@getCustomersByShop');

        /* products */
        Route::get('shops/{shop}/products', 'Admin\ProductController@getProductsByShop');

        Route::resource('products', 'Admin\ProductController', ['as' => 'vendor', 'except' => ['create', 'edit']]);
        Route::patch('products/toggle-enable/{product}', 'Admin\ProductController@toggleEnable');
        Route::post('products/status', 'Admin\ProductController@multipleStatusUpdate');
        Route::post('products/multiple-delete', 'Admin\ProductController@multipleDelete');

        Route::get('products/{product}/product-variations', 'Admin\ProductVariationController@getProductVariationsByProduct');
        Route::get('product-variations/{slug}', 'Admin\ProductVariationController@show');
        Route::post('product-variations', 'Admin\ProductVariationController@store');
        Route::put('product-variations/{slug}', 'Admin\ProductVariationController@update');
        Route::delete('product-variations/{slug}', 'Admin\ProductVariationController@destroy');

        Route::get('product-variations/{productVariation}/product-variation-values', 'Admin\ProductVariationValueController@getVariationValuesByVariation');
        Route::get('product-variation-values/{slug}', 'Admin\ProductVariationValueController@show');
        Route::post('product-variation-values', 'Admin\ProductVariationValueController@store');
        Route::put('product-variation-values/{slug}', 'Admin\ProductVariationValueController@update');
        Route::delete('product-variation-values/{slug}', 'Admin\ProductVariationValueController@destroy');

        Route::get('brands', 'Admin\BrandController@index');
        Route::post('brands', 'Admin\BrandController@store');

        Route::get('cities', 'Admin\CityController@index');
        Route::get('townships', 'Admin\TownshipController@index');
        Route::get('cities/{city}/townships', 'Admin\TownshipController@getTownshipsByCity');

        Route::get('customers', 'Admin\CustomerController@index');
        Route::post('customers', 'Admin\CustomerController@store');

        /* Address */
        Route::get('customers/{slug}/addresses', 'Admin\AddressController@index');
        Route::post('customers/{slug}/addresses', 'Admin\AddressController@store');

        Route::get('promocodes', 'Admin\PromocodeController@index');

        /* shop */

        Route::post('/register-device', 'Admin\UserController@registerToken');

        Route::post('excels/import/{type}', 'Excel\ExportImportController@import');
        Route::get('excels/export/{type}', 'Excel\ExportImportController@export');
        Route::get('excels/export/{type}/{params}', 'Excel\ExportImportController@exportWithParams');
    });
});

Route::group([
    'prefix' => 'v3/vendor',
    'namespace' => '\App\\Http\\Controllers\\Admin\\v3',
    'middleware' => ['cors', 'json.response', 'auth:vendors', 'user.enable'],
], function () {
    Route::resource('shop-orders', 'ShopOrderController', ['as' => 'vendor-v3-shop', 'except' => ['create', 'edit']]);
    Route::post('shop-orders/{shopOrder}/status', 'ShopOrderController@changeStatus');
    Route::get('shops/{shop}/orders', 'ShopOrderController@getVendorOrders');

    Route::resource('restaurant-orders', 'RestaurantOrderController', ['as' => 'vendor-v3-restaurant', 'except' => ['create', 'edit']]);
    Route::post('restaurant-orders/{restaurantOrder}/status', 'RestaurantOrderController@changeStatus');
    Route::get('restaurant-branches/{restaurantBranch}/orders', 'RestaurantOrderController@getBranchOrders');
});
