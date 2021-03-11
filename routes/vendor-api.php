<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'vendor'], function () {
    Route::post('login', 'Auth\VendorAuthController@login');
    Route::post('register', 'Auth\VendorAuthController@register');
    Route::middleware(['auth:vendors', 'user.enable'])->group(function () {
        Route::get('profile', 'Auth\VendorAuthController@getProfile');
        Route::put('profile/update', 'Auth\VendorAuthController@updateProfile');
        Route::post('refresh-token', 'Auth\VendorAuthController@refreshToken');
        Route::post('logout', 'Auth\VendorAuthController@logout');

        /* restaurant */
        /* restaurant categories */
        Route::get('restaurants/{slug}/restaurant-categories', 'RestaurantCategoryController@getCategoriesByRestaurant');
        Route::post('restaurants/add-restaurant-categories/{slug}', 'RestaurantController@addRestaurantCategories');
        Route::post('restaurants/remove-restaurant-categories/{slug}', 'RestaurantController@removeRestaurantCategories');
        Route::put('restaurant-branches/{slug}', 'RestaurantBranchController@updateWithTagsAndCategories');

        /* menus */
        Route::get('restaurant-branches/{slug}/menus', 'MenuController@getAvailableMenusByRestaurantBranch');
        Route::get('menus/{slug}', 'MenuController@show');
        Route::post('restaurant-branches/{slug}/menus', 'MenuController@createAvailableMenu');
        Route::post('menus', 'MenuController@store');
        Route::put('menus/{slug}', 'MenuController@update');
        Route::delete('menus/{slug}', 'MenuController@destory');
        Route::patch('menus/toggle-enable/{slug}', 'MenuController@toggleEnable');
        Route::post('restaurant-branches/add-available-menus/{slug}', 'RestaurantBranchController@addAvailableMenus');
        Route::post('restaurant-branches/remove-available-menus/{slug}', 'RestaurantBranchController@removeAvailableMenus');

        Route::get('menus/{slug}/menu-variations', 'MenuVariationController@getVariationsByMenu');
        Route::get('menu-variations/{slug}', 'MenuVariationController@show');
        Route::post('menu-variations', 'MenuVariationController@store');
        Route::put('menu-variations/{slug}', 'MenuVariationController@update');
        Route::delete('menu-variations/{slug}', 'MenuVariationController@destroy');

        Route::get('menu-variation-values/{slug}', 'MenuVariationValueController@show');
        Route::post('menu-variation-values', 'MenuVariationValueController@store');
        Route::put('menu-variation-values/{slug}', 'MenuVariationValueController@update');
        Route::delete('menu-variation-values/{slug}', 'MenuVariationValueController@destroy');

        Route::get('menus/{slug}/menu-toppings', 'MenuToppingController@getToppingsByMenu');
        Route::get('menu-toppings/{slug}', 'MenuToppingController@show');
        Route::post('menu-toppings', 'MenuToppingController@store');
        Route::put('menu-toppings/{slug}', 'MenuToppingController@update');
        Route::delete('menu-toppings/{slug}', 'MenuToppingController@destroy');
        /* restaurant */

        /* shop */
        /* shop categories */
        Route::resource('shops', 'ShopController');
        Route::get('shop-categories', 'ShopCategoryController@index');
        Route::get('shops/{slug}/shop-categories', 'ShopCategoryController@getCategoriesByShop');
        Route::post('shops/add-shop-categories/{slug}', 'ShopController@addShopCategories');
        Route::post('shops/remove-shop-categories/{slug}', 'ShopController@removeShopCategories');
        Route::get('shop-categories/{slug}/sub-categories', 'ShopSubCategoryController@getSubCategoriesByCategory');

        /* products */
        Route::get('shops/{slug}/products', 'ProductController@getProductsByShop');
        Route::get('products/{slug}', 'ProductController@show');
        Route::post('products', 'ProductController@store');
        Route::put('products/{slug}', 'ProductController@update');
        Route::delete('products/{slug}', 'ProductController@destroy');
        Route::patch('products/toggle-enable/{slug}', 'ProductController@toggleEnable');

        Route::get('products/{slug}/product-variations', 'ProductVariationController@getProductVariationsByProduct');
        Route::get('product-variations/{slug}', 'ProductVariationController@show');
        Route::post('product-variations', 'ProductVariationController@store');
        Route::put('product-variations/{slug}', 'ProductVariationController@update');
        Route::delete('product-variations/{slug}', 'ProductVariationController@destory');

        Route::get('product-variation/{slug}/product-variation-values', 'ProductVariationValueController@getProductVariationValuesByProductVariation');
        Route::get('product-variation-values/{slug}', 'ProductVariationValueController@show');
        Route::post('product-variation-values', 'ProductVariationValueController@store');
        Route::put('product-variation-values/{slug}', 'ProductVariationValueController@update');
        Route::delete('product-variation-values/{slug}', 'ProductVariationValueController@destory');

        Route::get('brands', 'BrandController@index');
        Route::post('brands', 'BrandController@store');

        Route::resource('cities', 'CityController');
        Route::resource('townships', 'TownshipController');
        Route::get('cities/{slug}/townships', 'TownshipController@getTownshipsByCity');
        Route::resource('customers', 'CustomerController');

        /* shop */
    });
});
