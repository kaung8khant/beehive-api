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

    public function getCategories(Request $request){
        
        $shopCategories = ShopCategory::with('shopSubCategories','shops.products')
        ->where('name', 'LIKE', '%' . $request->filter . '%')
        ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
        ->orWhere('slug', $request->filter)
        ->paginate($request->size)
        ->items();

        $shopCategories = $this->getProductFromShop($shopCategories);

        return $this->generateResponse($shopCategories,200);
    }


    public function getTags(Request $request)
    {
        $shopTags =  ShopTag::with('shops.products')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate($request->size)
            ->items();

        $shopTags =  $this->getProductFromShop($shopTags);

        return $this->generateResponse($shopTags, 200);
    }
    public function getByTag(Request $request, $slug)
    {

        $shopTag = ShopTag::with('shops','shops.products','shops.products.shop')->where('slug', $slug)->firstOrFail();

        $shopTag = $this->replaceShopWithProduct($shopTag);

        return $this->generateResponse($shopTag, 200);
    }
    public function getByCategory(Request $request,$slug)
    {
        $shopCategory = ShopCategory::with('shops','shops.products','shops.products.shop')->where('slug', $slug)->firstOrFail();
        $shopCategory = $this->replaceShopWithProduct($shopCategory);
        return  $this->generateResponse($shopCategory, 200);
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

    private function getProductFromShop($items)
    {
        foreach ($items as $item) {
            $item = $this->replaceShopWithProduct($item);
        }

        return $items;
    }

    private function replaceShopWithProduct($data)
    {
        $products = [];
        
        foreach ($data['shops'] as $shop) {
            array_push($products, $shop['products']);
        }

        $data['products'] = collect($products)->collapse()->values();
        unset($data['shops']);

        return $data;
    } 

    
}
