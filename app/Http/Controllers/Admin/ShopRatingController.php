<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopRating;

class ShopRatingController extends Controller
{
    public function getShopRatings(Shop $shop)
    {
        $ratings =  ShopRating::where('target_id', $shop->id)->paginate(10);
        return CollectionHelper::removePaginateLinks($ratings);
    }
}
