<?php

namespace App\Helpers;

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

    public static function getCategoryIdsByBranch($branchId)
    {
        return Cache::remember('category_ids_branch_' . $branchId, 86400, function () use ($branchId) {
            return DB::table('menus as m')
                ->join('restaurant_branch_menu_map as rbmm', 'rbmm.menu_id', '=', 'm.id')
                ->where('rbmm.restaurant_branch_id', $branchId)
                ->where('m.is_enable', 1)
                ->where('rbmm.is_available', 1)
                ->pluck('restaurant_category_id');
        });
    }

    public static function forgetCategoryIdsByBranchCache($menuId)
    {
        $branchIds = DB::table('restaurant_branch_menu_map')->where('menu_id', $menuId)->pluck('restaurant_branch_id');

        foreach ($branchIds as $branchId) {
            Cache::forget('category_ids_branch_' . $branchId);
        }
    }

    public static function getRestaurantCategory($categoryId)
    {
        return Cache::remember('category_id_' . $categoryId, 86400, function () use ($categoryId) {
            return RestaurantCategory::select('slug', 'name')->find($categoryId);
        });
    }

    public static function getShopIdsByTag($shopTagId)
    {
        return Cache::remember('shop_ids_tag_' . $shopTagId, 86400, function () use ($shopTagId) {
            return DB::table('shop_shop_tag_map')->where('shop_tag_id', $shopTagId)->pluck('shop_id');
        });
    }
}
