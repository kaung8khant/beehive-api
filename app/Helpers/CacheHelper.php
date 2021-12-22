<?php

namespace App\Helpers;

use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait CacheHelper
{
    public static function getRestaurantSearchRadius()
    {
        return Cache::rememberForever('restaurant_search_radius', function () {
            return DB::table('settings')->where('key', 'restaurant_search_radius')->value('value');
        });
    }

    public static function getAllSettings()
    {
        return Cache::rememberForever('all_settings', function () {
            return Setting::all();
        });
    }

    public static function getRestaurantCategoryById($categoryId)
    {
        return Cache::remember('restaurant_category_id_' . $categoryId, 86400, function () use ($categoryId) {
            return RestaurantCategory::select('slug', 'name')->find($categoryId);
        });
    }

    public static function getShopIdsByTag($shopTagId)
    {
        return Cache::remember('shop_ids_tag_' . $shopTagId, 86400, function () use ($shopTagId) {
            return DB::table('shop_shop_tag_map')->where('shop_tag_id', $shopTagId)->pluck('shop_id');
        });
    }

    public static function getShopIdBySlug($slug)
    {
        return Cache::remember('shop_id_slug_' . $slug, 86400, function () use ($slug) {
            return DB::table('shops')->where('slug', $slug)->value('id');
        });
    }

    public static function getShopCategoryIdBySlug($slug)
    {
        return Cache::remember('shop_category_id_slug_' . $slug, 86400, function () use ($slug) {
            return DB::table('shop_categories')->where('slug', $slug)->value('id');
        });
    }

    public static function getShopSubCategoryIdBySlug($slug)
    {
        return Cache::remember('shop_sub_category_id_slug_' . $slug, 86400, function () use ($slug) {
            return DB::table('shop_sub_categories')->where('slug', $slug)->value('id');
        });
    }

    public static function getBrandIdBySlug($slug)
    {
        return Cache::remember('brand_id_slug_' . $slug, 86400, function () use ($slug) {
            return DB::table('brands')->where('slug', $slug)->value('id');
        });
    }

    public static function getRestaurantIdBySlug($slug)
    {
        return Cache::remember('restaurant_id_slug_' . $slug, 86400, function () use ($slug) {
            return DB::table('restaurants')->where('slug', $slug)->value('id');
        });
    }

    public static function getRestaurantCategoryIdBySlug($slug)
    {
        return Cache::remember('restaurant_category_id_slug_' . $slug, 86400, function () use ($slug) {
            return DB::table('restaurant_categories')->where('slug', $slug)->value('id');
        });
    }
}
