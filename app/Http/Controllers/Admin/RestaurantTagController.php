<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantTag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantTagController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        $tags = RestaurantTag::search($request->filter)->paginate(10);
        $this->optimizeTags($tags);
        return CollectionHelper::removePaginateLinks($tags);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $tag = RestaurantTag::create($request->validate(
            [
                'name' => 'required|unique:restaurant_tags',
                'slug' => 'required|unique:restaurant_tags',
            ]
        ));

        return response()->json($tag, 201);
    }

    public function show(RestaurantTag $restaurantTag)
    {
        return response()->json($restaurantTag, 200);
    }

    public function update(Request $request, RestaurantTag $restaurantTag)
    {
        $restaurantTag->update($request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_tags')->ignore($restaurantTag->id),
            ],
        ]));

        return response()->json($restaurantTag, 200);
    }

    public function destroy(RestaurantTag $restaurantTag)
    {
        $restaurantTag->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    public function getTagsByRestaurant(Request $request, Restaurant $restaurant)
    {
        $tagIds = RestaurantTag::whereHas('restaurants', function ($query) use ($restaurant) {
            $query->where('id', $restaurant->id);
        })->pluck('id')->unique()->values()->toArray();

        $tags = RestaurantTag::search($request->filter)->whereIn('id', $tagIds)->paginate(10);
        $this->optimizeTags($tags);
        return CollectionHelper::removePaginateLinks($tags);
    }

    private function optimizeTags($tags)
    {
        $tags->makeHidden(['created_by', 'updated_by']);
    }
}
