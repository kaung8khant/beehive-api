<?php

namespace App\Http\Controllers;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Models\ShopTag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopTagController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('shop_tags', 'name', $request->by, $request->order);

        return ShopTag::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
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

    public function getTagsByShop(Request $request, $slug)
    {
        return ShopTag::whereHas('shops', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->paginate(10);
    }
}
