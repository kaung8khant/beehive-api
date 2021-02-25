<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Shop;

class ShopController extends Controller
{
    protected $customer;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer = Auth::guard('customers')->user();
        }
    }

    public function index(Request $request)
    {
        return Shop::with('shop_categories', 'shop_tags')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    public function show($slug)
    {
        return Shop::with('shop_categories', 'shop_tags')->where('slug', $slug)->first();
    }

    public function getFavoriteShops()
    {
        return $this->customer->shops()->with('shop_categories', 'shop_tags')->paginate(10);
    }

    public function setFavoriteShop($slug)
    {
        $shopId = $this->getShopId($slug);

        try {
            $this->customer->shops()->attach($shopId);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['message' => 'You already set favorite this shop.'], 409);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function removeFavoriteShop($slug)
    {
        $shopId = $this->getShopId($slug);

        $this->customer->shops()->detach($shopId);
        return response()->json(['message' => 'Success.'], 200);
    }

    private function getShopId($slug)
    {
        return Shop::where('slug', $slug)->firstOrFail()->id;
    }
}
