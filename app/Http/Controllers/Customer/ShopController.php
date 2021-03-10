<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Shop;
use App\Models\ShopCategory;
use Illuminate\Support\Facades\Log;
use App\Models\ShopTag;
use App\Helpers\ResponseHelper;
use App\Models\ShopSubCategory;

class ShopController extends Controller
{
    use ResponseHelper;

    protected $customer;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer = Auth::guard('customers')->user();
        }
    }

    public function index(Request $request)
    {
        $shop = Shop::with('availableCategories', 'availableTags')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate($request->size)->items();
        return $this->generateResponse($shop,200);
    }

    public function show($slug)
    {
        $shop =  Shop::with('availableCategories', 'availableTags')->where('slug', $slug)->first();
        return $this->generateResponse($shop,200);
    }

    public function getFavoriteShops(Request $request)
    {
        $shop = $this->customer->shops()->with('availableCategories', 'availableTags')->paginate($request->size)->items();
        return $this->generateResponse($shop,200);
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

    public function getCategories(){
        
        $categories = ShopCategory::with('shopSubCategories')->get();
        return $this->generateResponse($categories,200);
    }

    public function getTags(Request $request)
    {
        $shopTags =  ShopTag::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate($request->size)
            ->items();

        return $this->generateResponse($shopTags, 200);
    }
    public function getByTag(Request $request, $slug)
    {

        $shopTag = ShopTag::where('slug', $slug)->firstOrFail();
        $restaurants =  $shopTag->shops()->paginate($request->size)->items();

        return $this->generateResponse($restaurants, 200);
    }
    public function getByCategory(Request $request,$slug)
    {
        $shop = ShopCategory::with('shops')->where('slug', $slug)->paginate($request->size)->items();
        return  $this->generateResponse($shop, 200);
    }

    public function getBySubCategory(Request $request,$slug)
    {
        $shop = ShopSubCategory::with('shopCategory')->with('shopCategory.shops')->where('slug', $slug)->paginate($request->size)->items();
        return  $this->generateResponse($shop, 200);
    }
    

    private function getShopId($slug)
    {
        return Shop::where('slug', $slug)->firstOrFail()->id;
    }

    

    
}
