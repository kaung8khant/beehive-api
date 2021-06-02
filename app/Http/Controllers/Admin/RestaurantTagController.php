<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\RestaurantTag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantTagController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('restaurant_tags', 'name', $request->by, $request->order);

        return RestaurantTag::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
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

    public function getTagsByRestaurant(Request $request, $slug)
    {
        $sorting = CollectionHelper::getSorting('restaurant_tags', 'name', $request->by, $request->order);

        return RestaurantTag::whereHas('restaurants', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }
}
