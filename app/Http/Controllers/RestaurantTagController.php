<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\RestaurantTag;

class RestaurantTagController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return RestaurantTag::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $tag = RestaurantTag::create($request->validate(
            [
                'name' => 'required|unique:restaurant_tags',
                'name_mm' => 'nullable|unique:restaurant_tags',
                'slug' => 'required|unique:restaurant_tags',
            ]
        ));
        return response()->json($tag, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RestaurantTag  $tag
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(RestaurantTag::where('slug', $slug)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RestaurantTag  $slug
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $tag = RestaurantTag::where('slug', $slug)->firstOrFail();

        $tag->update($request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_tags')->ignore($tag->id),
            ],
            'name_mm' => [
                'nullable',
                Rule::unique('restaurant_tags')->ignore($tag->id),
            ],
        ]));

        return response()->json($tag, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RestaurantTag  $slug
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        RestaurantTag::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    /**
     * Display a listing of the restaurant tags by one restaurant.
     */
    public function getTagsByRestaurant(Request $request, $slug)
    {
        return RestaurantTag::whereHas('restaurants', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter .'%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter);
        })->paginate(10);
    }
}
