<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CacheHelper;
use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuTopping;
use App\Models\MenuVariant;
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

        $menus = Menu::with(['restaurant', 'restaurantCategory','menuVariants'])
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
        $validatedData = $this->validateRequest($request, true);

        $menu = Menu::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'menus', $menu->slug);
        }

        if (isset($validatedData['menu_variants'])) {
            $this->createMenuVariants($menu->id, $validatedData['menu_variants']);
        }

        if (isset($validatedData['menu_toppings'])) {
            $this->createToppings($menu->id, $validatedData['menu_toppings']);
        }

        $restaurantBranches = CacheHelper::getAllRestaurantBranchesByRestaurantId($validatedData['restaurant_id']);

        foreach ($restaurantBranches as $branch) {
            $branch->availableMenus()->attach($menu->id);
        }

        return response()->json($menu->load('restaurant'), 200);
    }

    public function show(Menu $menu)
    {
        return response()->json($menu->load(['restaurant', 'restaurantCategory', 'menuVariations', 'menuVariations.menuVariationValues', 'menuVariants', 'menuToppings']), 200);
    }

    public function update(Request $request, Menu $menu)
    {
        $validatedData = $this->validateRequest($request);
        $oldRestaurantId = $menu->restaurant_id;

        $menu->update($validatedData);
        CacheHelper::forgetCategoryIdsByBranchCache($menu->id);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'menus', $menu->slug);
        }

        if (isset($validatedData['menu_variants'])) {
            $menu->menuVariants()->delete();
            $this->createMenuVariants($menu->id, $validatedData['menu_variants']);
        } else {
            $menu->menuVariants()->delete();
        }

        if (isset($validatedData['menu_toppings'])) {
            $menu->menuToppings()->delete();
            $this->createToppings($menu->id, $validatedData['menu_toppings']);
        } else {
            $menu->menuToppings()->delete();
        }

        if ($oldRestaurantId !== $validatedData['restaurant_id']) {
            $newBranches = CacheHelper::getAllRestaurantBranchesByRestaurantId($validatedData['restaurant_id']);
            $oldBranches = CacheHelper::getAllRestaurantBranchesByRestaurantId($oldRestaurantId);

            foreach ($oldBranches as $branch) {
                $branch->availableMenus()->detach($menu->id);
            }

            foreach ($newBranches as $branch) {
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

    private function validateRequest($request, $slug = false)
    {
        $params = [
            'name' => 'required',
            'description' => 'nullable',
            'is_enable' => 'required|boolean',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'restaurant_category_slug' => 'required|exists:App\Models\RestaurantCategory,slug',
            'image_slug' => 'nullable|exists:App\Models\File,slug',

            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string',
            'variants.*.values' => 'required|array',

            'menu_variants' => 'required_with:variants',
            'menu_variants.*.variant' => 'required',
            'menu_variants.*.price' => 'required|numeric',
            'menu_variants.*.tax' => 'required|numeric',
            'menu_variants.*.discount' => 'required|numeric',
            'menu_variants.*.is_enable' => 'required|boolean',
            'menu_variants.*.image_slug' => 'nullable|exists:App\Models\File,slug',

            'menu_toppings' => 'nullable|array',
            'menu_toppings.*.name' => 'required|string',
            'menu_toppings.*.price' => 'required|numeric',
            'menu_toppings.*.is_incremental' => 'required|boolean',
            'menu_toppings.*.max_quantity' => 'nullable|max:10',
            'menu_toppings.*.image_slug' => 'nullable|exists:App\Models\File,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:menus';
        }

        $validatedData = $request->validate($params);

        $validatedData['restaurant_id'] = CacheHelper::getRestaurantIdBySlug($request->restaurant_slug);
        $validatedData['restaurant_category_id'] = CacheHelper::getRestaurantCategoryIdBySlug($request->restaurant_category_slug);

        return $validatedData;
    }

    private function createMenuVariants($menuId, $menuVariants)
    {
        foreach ($menuVariants as $variant) {
            $variant['menu_id'] = $menuId;
            $variant['slug'] = $this->generateUniqueSlug();

            MenuVariant::create($variant);

            if (isset($variant['image_slug'])) {
                $this->updateFile($variant['image_slug'], 'menu_variants', $variant['slug']);
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
            ->with('restaurantCategory', 'menuVariations', 'menuVariations.menuVariationValues', 'menuVariants', 'menuToppings')
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
}
