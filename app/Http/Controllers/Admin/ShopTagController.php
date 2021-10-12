<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ShopTagController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        $shopTags = ShopTag::search($request->filter)->paginate(10);
        $this->optimizeShopTags($shopTags);
        return CollectionHelper::removePaginateLinks($shopTags);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $tag = ShopTag::create($request->validate(
            [
                'name' => 'required|unique:shop_tags',
                'slug' => 'required|unique:shop_tags',
            ]
        ));

        return response()->json($tag, 201);
    }

    public function show(ShopTag $shopTag)
    {
        return response()->json($shopTag, 200);
    }

    public function update(Request $request, ShopTag $shopTag)
    {
        $shopTag->update($request->validate([
            'name' => [
                'required',
                Rule::unique('shop_tags')->ignore($shopTag->id),
            ],
        ]));

        Cache::forget('shop_ids_tag_' . $shopTag->id);
        return response()->json($shopTag, 200);
    }

    public function destroy(ShopTag $shopTag)
    {
        Cache::forget('shop_ids_tag_' . $shopTag->id);
        $shopTag->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    public function getTagsByShop(Request $request, Shop $shop)
    {
        $tagIds = ShopTag::whereHas('shops', function ($query) use ($shop) {
            $query->where('id', $shop->id);
        })->pluck('id')->toArray();

        $shopTags = ShopTag::search($request->filter)->whereIn('id', $tagIds)->paginate(10);
        $this->optimizeShopTags($shopTags);
        return CollectionHelper::removePaginateLinks($shopTags);
    }

    private function optimizeShopTags($shopTags)
    {
        $shopTags->makeHidden(['id', 'created_by', 'updated_by']);
    }
}
