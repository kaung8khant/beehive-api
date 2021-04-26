<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopRating;
use Illuminate\Http\Request;

class ShopRatingController extends Controller
{

    public function getShopRatings($slug)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();
        return ShopRating::where('target_id', $shop->id)
            ->paginate(10);
    }
}
