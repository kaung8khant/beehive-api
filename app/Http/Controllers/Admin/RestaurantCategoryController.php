<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use App\Models\RestaurantCategorySorting;
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

        Cache::forget('restaurant_category_id_' . $restaurantCategory->id);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'restaurant_categories', $restaurantCategory->slug);
        }

        return response()->json($restaurantCategory, 200);
    }

    public function destroy(RestaurantCategory $restaurantCategory)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        foreach ($restaurantCategory->images as $image) {
            $this->deleteFile($image->slug);
        }

        $restaurantCategory->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    public function getCategoriesByRestaurant(Restaurant $restaurant)
    {
        return Menu::where('restaurant_id', $restaurant->id)
            ->pluck('restaurant_category_id')
            ->unique()
            ->map(function ($categoryId) use ($restaurant) {
                $category = RestaurantCategory::where('id', $categoryId)->exclude(['created_by', 'updated_by'])->first();
                $searchIndex = RestaurantCategorySorting::where('restaurant_id', $restaurant->id)
                    ->where('restaurant_category_id', $category->id)
                    ->value('search_index');
                $category['search_index'] = $searchIndex ? $searchIndex : 0;

                return $category;
            })
            ->sortBy([
                ['search_index', 'desc'],
                ['name', 'asc'],
            ])
            ->map(function ($category) use ($restaurant) {
                $category['menus'] = Menu::where('restaurant_id', $restaurant->id)
                    ->where('restaurant_category_id', $category->id)
                    ->exclude(['created_by', 'updated_by'])
                    ->orderBy('search_index', 'desc')
                    ->orderBy('name', 'asc')
                    ->get();

                return $category;
            });
    }

    public function updateSearchIndex(Request $request, Restaurant $restaurant)
    {
        $validatedData = $request->validate([
            '*' => 'required|array',
            '*.category_slug' => 'required|exists:App\Models\RestaurantCategory,slug',
            '*.search_index' => 'required|integer',
        ]);

        foreach ($validatedData as $category) {
            $restaurantCategory = RestaurantCategory::where('slug', $category['category_slug'])->first();

            $categorySorting = RestaurantCategorySorting::where('restaurant_id', $restaurant->id)
                ->where('restaurant_category_id', $restaurantCategory->id)
                ->first();

            if ($categorySorting) {
                $categorySorting->update([
                    'search_index' => $category['search_index'],
                ]);
            } else {
                RestaurantCategorySorting::create([
                    'search_index' => $category['search_index'],
                    'restaurant_id' => $restaurant->id,
                    'restaurant_category_id' => $restaurantCategory->id,
                ]);
            }
        }

        return response()->json(['message' => 'success'], 200);
    }
}
