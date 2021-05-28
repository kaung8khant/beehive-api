<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\MenuTopping;
use App\Models\MenuVariation;
use App\Models\MenuVariationValue;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    use FileHelper, ResponseHelper, StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('menus', 'id', $request->by ? $request->by : 'desc', $request->order);

        $menus = Menu::with(['restaurant', 'restaurantCategory'])
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            });

        if (isset($request->is_enable)) {
            $menus = $menus->where('is_enable', $request->is_enable)
                ->whereHas('restaurant', function ($query) use ($request) {
                    $query->where('is_enable', $request->is_enable);
                });
        }

        return $menus->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(true));
        $restaurant = Restaurant::where('slug', $request->restaurant_slug)->firstOrFail();
        $validatedData['restaurant_id'] = $restaurant->id;
        $validatedData['restaurant_category_id'] = $this->getRestaurantCategoryId($request->restaurant_category_slug);

        $menu = Menu::create($validatedData);
        $menuId = $menu->id;

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'menus', $menu->slug);
        }

        $this->createVariations($menuId, $validatedData['menu_variations']);
        $this->createToppings($menuId, $validatedData['menu_toppings']);

        foreach ($restaurant->restaurantBranches as $branch) {
            $availableMenus = Menu::where('slug', $menu->slug)->pluck('id');
            $branch->availableMenus()->attach($availableMenus);
        }

        return response()->json($menu->load('restaurant'), 200);
    }

    public function show(Menu $menu)
    {
        return response()->json($menu->load(['restaurant', 'restaurantCategory', 'menuVariations', 'menuVariations.menuVariationValues', 'menuToppings']), 200);
    }

    public function update(Request $request, Menu $menu)
    {
        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['restaurant_id'] = $this->getRestaurantId($request->restaurant_slug);
        $validatedData['restaurant_category_id'] = $this->getRestaurantCategoryId($request->restaurant_category_slug);

        CacheHelper::forgetCategoryIdsByBranchCache($menu->id);
        $menu->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'menus', $menu->slug);
        }

        if ($request->menu_variations) {
            $menu->menuVariations()->delete();
            $this->createVariations($menu->id, $validatedData['menu_variations']);
        }

        if ($request->menu_toppings) {
            $menu->menuToppings()->delete();
            $this->createToppings($menu->id, $validatedData['menu_toppings']);
        }

        if ($menu->restaurant_id!==$validatedData['restaurant_id']) {
            $restaurant = Restaurant::where('slug', $request->restaurant_slug)->firstOrFail();
            $oldRestaurant = Restaurant::where('id', $menu->restaurant_id)->firstOrFail();
            foreach ($oldRestaurant->restaurantBranches as $branch) {
                $branch->availableMenus()->detach($menu->id);
            }
            foreach ($restaurant->restaurantBranches as $branch) {
                $branch->availableMenus()->attach($menu->id);
            }
        }

        return response()->json($menu->load('restaurant'), 200);
    }

    public function destroy(Menu $menu)
    {
        foreach ($menu->images as $image) {
            $this->deleteFile($image->slug);
        }

        CacheHelper::forgetCategoryIdsByBranchCache($menu->id);
        $menu->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function getMenusByRestaurant(Request $request, Restaurant $restaurant)
    {
        $sorting = CollectionHelper::getSorting('menus', 'name', $request->by, $request->order);

        $menus = Menu::with('restaurantCategory')
            ->where('restaurant_id', $restaurant->id)
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            });

        if (isset($request->is_enable)) {
            $menus = $menus->where('is_enable', $request->is_enable)
                ->whereHas('restaurant', function ($query) use ($request) {
                    $query->where('is_enable', $request->is_enable);
                });
        }

        return $menus->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function getMenusByBranch(Request $request, RestaurantBranch $restaurantBranch)
    {
        $sorting = CollectionHelper::getSorting('menus', 'name', $request->by, $request->order);

        $menus = $restaurantBranch->availableMenus()
            ->with('restaurantCategory')
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            });

        if (isset($request->is_enable)) {
            $menus = $menus->where('is_enable', $request->is_enable)
                ->whereHas('restaurant', function ($query) use ($request) {
                    $query->where('is_enable', $request->is_enable);
                });
        }

        $menus = $menus->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);

        foreach ($menus as $menu) {
            $menu->setAppends(['is_available', 'images']);
        }

        return $menus;
    }

    public function getMenusByCategory(Request $request, RestaurantCategory $restaurantCategory)
    {
        $sorting = CollectionHelper::getSorting('menus', 'name', $request->by, $request->order);

        $menus = Menu::with('restaurant', 'restaurantCategory')
            ->where('restaurant_category_id', $restaurantCategory->id)
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            });

        if (isset($request->is_enable)) {
            $menus = $menus->where('is_enable', $request->is_enable)
                ->whereHas('restaurant', function ($query) use ($request) {
                    $query->where('is_enable', $request->is_enable);
                });
        }

        return $menus->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function getMenusByBranchWithAdditionals(Request $request, RestaurantBranch $restaurantBranch)
    {
        $sorting = CollectionHelper::getSorting('menus', 'name', $request->by, $request->order);

        $menus = $restaurantBranch->availableMenus()
            ->with('restaurantCategory', 'menuVariations', 'menuVariations.menuVariationValues', 'menuToppings')
            ->where('is_available', true)
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            });

        if (isset($request->is_enable)) {
            $menus = $menus->where('is_enable', $request->is_enable)
                ->whereHas('restaurant', function ($query) use ($request) {
                    $query->where('is_enable', $request->is_enable);
                });
        }

        return $menus->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'name' => 'required',
            'description' => 'nullable',
            'price' => 'required|numeric',
            'tax' => 'required|numeric',
            'discount' => 'required|numeric',
            'is_enable' => 'required|boolean',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'restaurant_category_slug' => 'required|exists:App\Models\RestaurantCategory,slug',
            'menu_variations' => 'nullable|array',
            'menu_variations.*.name' => 'required|string',
            'menu_toppings' => 'nullable|array',
            'menu_toppings.*.name' => 'required|string',
            'menu_toppings.*.price' => 'required|numeric',
            'menu_toppings.*.is_incremental' => 'required|boolean',
            'menu_toppings.*.max_quantity' => 'nullable|max:10',
            'menu_toppings.*.image_slug' => 'nullable|exists:App\Models\File,slug',
            'menu_variations.*.menu_variation_values' => 'required|array',
            'menu_variations.*.menu_variation_values.*.value' => 'required|string',
            'menu_variations.*.menu_variation_values.*.price' => 'required|numeric',
            'menu_variations.*.menu_variation_values.*.image_slug' => 'nullable|exists:App\Models\File,slug',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:menus';
        }

        return $params;
    }

    private function createVariations($menuId, $variations)
    {
        foreach ($variations as $variation) {
            $variation['slug'] = $this->generateUniqueSlug();
            $variation['menu_id'] = $menuId;
            $menuVariation = MenuVariation::create($variation);
            $variationId = $menuVariation->id;
            $this->createVariationValues($variationId, $variation['menu_variation_values']);
        }
    }

    private function createVariationValues($variationId, $variationValues)
    {
        foreach ($variationValues as $variationValue) {
            $variationValue['slug'] = $this->generateUniqueSlug();
            $variationValue['menu_variation_id'] = $variationId;
            MenuVariationValue::create($variationValue);
            if (!empty($variationValue['image_slug'])) {
                $this->updateFile($variationValue['image_slug'], 'menu_variation_values', $variationValue['slug']);
            }
        }
    }

    private function createToppings($menuId, $toppings)
    {
        foreach ($toppings as $topping) {
            $topping['slug'] = $this->generateUniqueSlug();
            $topping['menu_id'] = $menuId;
            MenuTopping::create($topping);
            if (!empty($topping['image_slug'])) {
                $this->updateFile($topping['image_slug'], 'menu_toppings', $topping['slug']);
            }
        }
    }

    private function getRestaurantId($slug)
    {
        return Restaurant::where('slug', $slug)->first()->id;
    }

    private function getRestaurantCategoryId($slug)
    {
        return RestaurantCategory::where('slug', $slug)->first()->id;
    }

    public function toggleEnable(Menu $menu)
    {
        CacheHelper::forgetCategoryIdsByBranchCache($menu->id);
        $menu->update(['is_enable' => !$menu->is_enable]);
        return response()->json(['message' => 'Success.'], 200);
    }

    public function multipleStatusUpdate(Request $request)
    {
        $validatedData = $request->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\Menu,slug',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $menu = Menu::where('slug', $slug)->firstOrFail();

            CacheHelper::forgetCategoryIdsByBranchCache($menu->id);
            $menu->update(['is_enable' => $request->is_enable]);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function multipleDelete(Request $request)
    {
        $validatedData = $request->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\Menu,slug',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $menu = Menu::where('slug', $slug)->firstOrFail();

            foreach ($menu->images as $image) {
                $this->deleteFile($image->slug);
            }

            CacheHelper::forgetCategoryIdsByBranchCache($menu->id);
            $menu->delete();
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function import(Request $request)
    {
        $validatedData = $request->validate([
            'menus' => 'nullable|array',
            'menus.*.name' => 'required',
            'menus.*.description' => 'nullable',
            'menus.*.price' => 'required|numeric',
            'menus.*.tax' => 'required|numeric',
            'menus.*.discount' => 'required|numeric',
            'menus.*.is_enable' => 'required|boolean',
            'menus.*.restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'menus.*.restaurant_category_slug' => 'required|exists:App\Models\RestaurantCategory,slug',
        ]);

        foreach ($validatedData['menus'] as $data) {
            $data['slug'] = $this->generateUniqueSlug();
            $restaurant = Restaurant::where('slug', $data['restaurant_slug'])->firstOrFail();
            $data['restaurant_id'] = $restaurant->id;
            $data['restaurant_category_id'] = $this->getRestaurantCategoryId($data['restaurant_category_slug']);
            $menu = Menu::create($data);
            foreach ($restaurant->restaurantBranches as $branch) {
                $availableMenus = Menu::where('slug', $menu->slug)->pluck('id');
                $branch->availableMenus()->attach($availableMenus);
            }
        }

        return response()->json(['message' => 'Success.'], 200);
    }
}
