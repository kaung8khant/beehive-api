<?php

namespace App\Http\Controllers;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\RestaurantCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class RestaurantCategoryController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('restaurant_categories', 'name', $request->by, $request->order);

        return RestaurantCategory::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $restaurantCategory = RestaurantCategory::create($request->validate([
            'name' => 'required|unique:restaurant_categories',
            'slug' => 'required|unique:restaurant_categories',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]));

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'restaurant_categories', $restaurantCategory->slug);
        }

        return response()->json($restaurantCategory, 201);
    }

    public function show(RestaurantCategory $restaurantCategory)
    {
        return response()->json($restaurantCategory, 200);
    }

    public function update(Request $request, RestaurantCategory $restaurantCategory)
    {
        $restaurantCategory->update($request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_categories')->ignore($restaurantCategory->id),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]));

        Cache::forget('category_id_' . $restaurantCategory->id);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'restaurant_categories', $restaurantCategory->slug);
        }

        return response()->json($restaurantCategory, 200);
    }

    public function destroy(RestaurantCategory $restaurantCategory)
    {
        foreach ($restaurantCategory->images as $image) {
            $this->deleteFile($image->slug);
        }

        $restaurantCategory->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    public function getCategoriesByRestaurant(Request $request, $slug)
    {
        $sorting = CollectionHelper::getSorting('restaurant_categories', 'name', $request->by, $request->order);

        return RestaurantCategory::whereHas('restaurants', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function import(Request $request)
    {
        $validatedData = $request->validate([
            'restaurant_categories' => 'nullable|array',
            'restaurant_categories.*.name' => 'required|unique:restaurant_categories',
        ]);

        foreach ($validatedData['restaurant_categories'] as $data) {
            $data['slug'] = $this->generateUniqueSlug();
            RestaurantCategory::create($data);
        }

        return response()->json(['message' => 'Success.'], 200);
    }
}
