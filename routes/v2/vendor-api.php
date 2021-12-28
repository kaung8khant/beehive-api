<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v2/vendor',
    'middleware' => ['cors', 'json.response'],
], function () {
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
        Route::get('dashboard/branch-earnings', 'Dashboard\VendorDashboardController@getCentralRestaurantBranchEarning');
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
        Route::put('restaurants/{restaurant}', 'Admin\RestaurantController@update');


        /* menus */
        Route::get('restaurants/{restaurant}/menus', 'Admin\MenuController@getMenusByRestaurant');
        Route::get('restaurant-branches/{restaurantBranch}/menus', 'Admin\MenuController@getMenusByBranch');
        Route::get('restaurant-branches/{restaurantBranch}/menus-with-additionals', 'Admin\MenuController@getMenusByBranchWithAdditionals');
        Route::post('menus/status', 'Admin\MenuController@multipleStatusUpdate');
        Route::patch('menus/toggle-enable/{slug}', 'Admin\MenuController@toggleEnable');
        Route::post('menus/multiple-delete', 'Admin\MenuController@multipleDelete');
        Route::resource('menus', 'Admin\MenuController', ['as' => 'vendor', 'except' => ['create', 'edit']]);

        Route::get('restaurants/branches/{restaurantBranch}/menus', 'Customer\MenuController@getAvailableMenusByBranch');

        Route::post('restaurant-branches/{restaurantBranch}/menus/{menu}', 'Admin\RestaurantBranchController@toggleAvailable');

        Route::get('menu-toppings/{slug}', 'Admin\MenuToppingController@show');
        Route::post('menu-toppings', 'Admin\MenuToppingController@store');
        Route::put('menu-toppings/{menuTopping}', 'Admin\MenuToppingController@update');
        Route::delete('menu-toppings/{menuTopping}', 'Admin\MenuToppingController@destroy');
        Route::put('menus/{menu}/variants', 'Admin\MenuVariantController@updateVariants');
        Route::patch('menus/variants/{menuVariant:slug}/enable', 'Admin\MenuVariantController@toggleEnable');
        Route::get('restaurant-branches/{restaurantBranch}/customers', 'Admin\CustomerController@getCustomersByBranch');
        Route::get('restaurants/{restaurant}/customers', 'Admin\CustomerController@getCustomersByRestaurant');
        /* restaurant */

        /* shop */
        /* shop categories */
        Route::resource('shops', 'Admin\ShopController', ['as' => 'vendor']);
        Route::get('shop-tags', 'Admin\ShopTagController@index');
        Route::get('shop-categories', 'Admin\ShopCategoryController@index');
        Route::get('shops/{shop}/shop-categories', 'Admin\ShopCategoryController@getCategoriesByShop');
        Route::post('shops/add-shop-categories/{slug}', 'Admin\ShopController@addShopCategories');
        Route::post('shops/remove-shop-categories/{slug}', 'Admin\ShopController@removeShopCategories');
        Route::get('shop-categories/{shopCategory}/sub-categories', 'Admin\ShopSubCategoryController@getSubCategoriesByCategory');
        Route::get('shops/{shop}/customers', 'Admin\CustomerController@getCustomersByShop');
        /* shop */

        /* products */
        Route::get('shops/{shop}/products', 'Admin\ProductController@getProductsByShop');

        Route::resource('products', 'Admin\ProductController', ['as' => 'vendor', 'except' => ['create', 'edit']]);
        Route::patch('products/toggle-enable/{product}', 'Admin\ProductController@toggleEnable');
        Route::post('products/status', 'Admin\ProductController@multipleStatusUpdate');
        Route::post('products/multiple-delete', 'Admin\ProductController@multipleDelete');
        Route::put('products/{product}/variants', 'Admin\ProductVariantController@updateVariants');
        Route::patch('products/variants/{productVariant:slug}/enable', 'Admin\ProductVariantController@toggleEnable');
        /* products */

        Route::get('brands', 'Admin\BrandController@index');
        Route::post('brands', 'Admin\BrandController@store');

        Route::get('customers', 'Admin\CustomerController@index');
        Route::post('customers', 'Admin\CustomerController@store');

        /* Address */
        Route::get('customers/{slug}/addresses', 'Admin\AddressController@index');
        Route::post('customers/{slug}/addresses', 'Admin\AddressController@store');

        Route::get('promocodes', 'Admin\PromocodeController@index');

        Route::post('/register-device', 'Admin\UserController@registerToken');

        Route::post('excels/import/{type}', 'Excel\ExportImportController@import');
        Route::get('excels/export/{type}', 'Excel\ExportImportController@export');
        Route::get('excels/export/{type}/{params}', 'Excel\ExportImportController@exportWithParams');

        Route::get('reports/restaurant-orders/vendor/{slug}', 'Report\RestaurantOrderController@getVendorOrders');
        Route::get('reports/restaurant-orders/branch/{slug}', 'Report\RestaurantOrderController@getBranchOrders');
        Route::get('reports/shop-orders/vendor/{shop}/product-by-shop-sales', 'Report\ShopOrderController@getProductByShopSaleReport');

        Route::post('devices', 'OneSignal\OneSignalController@registerAdminDevice');

        Route::get('shops/{shop}/commissions', 'Admin\CommissionController@getOneShopOrderCommissions');
        Route::get('restaurant-branches/{restaurantBranch}/commissions', 'Admin\CommissionController@getRestaurantBranchOrderCommissions');
    });
});
