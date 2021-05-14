<?php

namespace App\Helpers;

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
}
