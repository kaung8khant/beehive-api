<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopRating;

class ShopRatingController extends Controller
{
    public function getShopRatings(Shop $shop)
    {
        return ShopRating::where('target_id', $shop->id)->paginate(10);
    }
}
