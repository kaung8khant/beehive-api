<?php

namespace App\Http\Controllers\Admin;

use App\Events\DataChanged;
use App\Helpers\CacheHelper;
use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuOption;
use App\Models\MenuOptionItem;
use App\Models\MenuTopping;
use App\Models\MenuVariant;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    use FileHelper, ResponseHelper, StringHelper;

    private $user;

    public function __construct()
    {
        if (Auth::guard('users')->check()) {
            $this->user = Auth::guard('users')->user();
        }
    }

    public function index(Request $request)
    {
        $menus = Menu::search($request->filter);

        if (isset($request->is_enable)) {
            $menuIds = Menu::whereHas('restaurant', function ($query) use ($request) {
                $query->where('is_enable', $request->is_enable);
            })->pluck('id')->toArray();

            $menus = $menus->where('is_enable', $request->is_enable)
                ->whereIn('id', $menuIds);
        }

        $menus = $menus->paginate(10);
        $this->optimizeMenus($menus);
        return CollectionHelper::removePaginateLinks($menus);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();
        $validatedData = $this->validateRequest($request, true);

        $menu = Menu::create($validatedData);
        DataChanged::dispatch($this->user, 'create', 'menus', $request->slug, $request->url(), 'success', $request->all());

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'menus', $menu->slug, $this->user, $request->url());
        }

        if (isset($validatedData['menu_variants'])) {
            $this->createMenuVariants($request, $menu->id, $validatedData['menu_variants']);
        }

        if (isset($validatedData['menu_toppings'])) {
            $this->createToppings($request, $menu->id, $validatedData['menu_toppings']);
        }

        if (isset($validatedData['menu_options'])) {
            $this->createOptions($request, $menu->id, $validatedData['menu_options']);
        }

        $restaurantBranches = CacheHelper::getAllRestaurantBranchesByRestaurantId($validatedData['restaurant_id']);

        foreach ($restaurantBranches as $branch) {
            $branch->availableMenus()->attach($menu->id);
        }

        return response()->json($menu->load('restaurant'), 200);
    }

    public function show(Menu $menu)
    {
        return response()->json($menu->load(['restaurant', 'restaurantCategory', 'menuVariations', 'menuVariations.menuVariationValues', 'menuVariants', 'menuToppings', 'menuOptions', 'menuOptions.options']), 200);
    }

    public function update(Request $request, Menu $menu)
    {
        $validatedData = $this->validateRequest($request);
        $oldRestaurantId = $menu->restaurant_id;

        $menu->update($validatedData);
        CacheHelper::forgetCategoryIdsByBranchCache($menu->id);
        DataChanged::dispatch($this->user, 'update', 'menus', $menu->slug, $request->url(), 'success', $request->all());

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'menus', $menu->slug, $this->user, $request->url());
        }

        if (isset($validatedData['menu_variants'])) {
            $menu->menuVariants()->delete();
            $this->createMenuVariants($request, $menu->id, $validatedData['menu_variants']);
        } else {
            $menu->menuVariants()->delete();
        }

        if (isset($validatedData['menu_toppings'])) {
            $menu->menuToppings()->delete();
            $this->createToppings($request, $menu->id, $validatedData['menu_toppings']);
        } else {
            $menu->menuToppings()->delete();
        }

        if (isset($validatedData['menu_options'])) {
            $menu->menuOptions()->delete();
            $this->createOptions($request, $menu->id, $validatedData['menu_options']);
        } else {
            $menu->menuOptions()->delete();
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

    public function destroy(Request $request, Menu $menu)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        foreach ($menu->images as $image) {
            $this->deleteFile($image->slug);
        }

        DataChanged::dispatch($this->user, 'delete', 'menus', $menu->slug, $request->url(), 'success');
        CacheHelper::forgetCategoryIdsByBranchCache($menu->id);
        $menu->delete();

        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function validateRequest($request, $slug = false)
    {
        $params = [
            'name' => 'required',
            'description' => 'nullable',
            'price' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
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

            'menu_options' => 'nullable|array',
            'menu_options.*.name' => 'required|string',
            'menu_options.*.max_choice' => 'required',
            'menu_options.*.options' => 'required|array',
            'menu_options.*.options.*.name' => 'required|string',
            'menu_options.*.options.*.price' => 'required|numeric',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:menus';
        }

        $validatedData = $request->validate($params);

        $validatedData['restaurant_id'] = CacheHelper::getRestaurantIdBySlug($request->restaurant_slug);
        $validatedData['restaurant_category_id'] = CacheHelper::getRestaurantCategoryIdBySlug($request->restaurant_category_slug);

        return $validatedData;
    }

    private function createMenuVariants($request, $menuId, $menuVariants)
    {
        foreach ($menuVariants as $variant) {
            $variant['menu_id'] = $menuId;
            $variant['slug'] = $this->generateUniqueSlug();

            MenuVariant::create($variant);
            DataChanged::dispatch($this->user, 'create', 'menu_variants', $variant['slug'], $request->url(), 'success', $variant);

            if (isset($variant['image_slug'])) {
                $this->updateFile($variant['image_slug'], 'menu_variants', $variant['slug']);
            }
        }
    }

    private function createToppings($request, $menuId, $toppings)
    {
        foreach ($toppings as $topping) {
            $topping['slug'] = $this->generateUniqueSlug();
            $topping['menu_id'] = $menuId;

            MenuTopping::create($topping);
            DataChanged::dispatch($this->user, 'create', 'menu_toppings', $topping['slug'], $request->url(), 'success', $topping);

            if (!empty($topping['image_slug'])) {
                $this->updateFile($topping['image_slug'], 'menu_toppings', $topping['slug']);
            }
        }
    }

    private function createOptions($request, $menuId, $options)
    {
        foreach ($options as $option) {
            $option['slug'] = $this->generateUniqueSlug();
            $option['menu_id'] = $menuId;

            $menuOption = MenuOption::create($option);
            DataChanged::dispatch($this->user, 'create', 'menu_options', $option['slug'], $request->url(), 'success', $option);

            foreach ($option['options'] as $item) {
                $item['menu_option_id'] = $menuOption->id;
                $item['slug'] = $this->generateUniqueSlug();
                MenuOptionItem::create($item);

                DataChanged::dispatch($this->user, 'create', 'menu_option_items', $item['slug'], $request->url(), 'success', $item);
            }
        }
    }

    public function toggleEnable(Request $request, Menu $menu)
    {
        $menu->update(['is_enable' => !$menu->is_enable]);
        CacheHelper::forgetCategoryIdsByBranchCache($menu->id);

        $status = $menu->is_enable ? 'enable' : 'disable';
        DataChanged::dispatch($this->user, $status, 'menus', $menu->slug, $request->url(), 'success');

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

            $menu->update(['is_enable' => $request->is_enable]);
            CacheHelper::forgetCategoryIdsByBranchCache($menu->id);

            $status = $menu->is_enable ? 'enable' : 'disable';
            DataChanged::dispatch($this->user, $status, 'menus', $menu->slug, $request->url(), 'success');
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function multipleDelete(Request $request)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        $validatedData = $request->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\Menu,slug',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $menu = Menu::where('slug', $slug)->firstOrFail();

            foreach ($menu->images as $image) {
                $this->deleteFile($image->slug);
            }

            DataChanged::dispatch($this->user, 'delete', 'menus', $menu->slug, $request->url(), 'success');
            CacheHelper::forgetCategoryIdsByBranchCache($menu->id);
            $menu->delete();
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function getMenusByRestaurant(Request $request, Restaurant $restaurant)
    {
        $menus = Menu::search($request->filter)->where('restaurant_id', $restaurant->id);

        if (isset($request->is_enable)) {
            $menuIds = Menu::whereHas('restaurant', function ($query) use ($request) {
                $query->where('is_enable', $request->is_enable);
            })->pluck('id')->toArray();

            $menus = $menus->where('is_enable', $request->is_enable)
                ->whereIn('id', $menuIds);
        }

        $menus = $menus->get();
        $this->optimizeMenus($menus);
        return $menus;
    }

    public function getMenusByBranch(Request $request, RestaurantBranch $restaurantBranch)
    {
        $availableMenus = DB::table('restaurant_branch_menu_map')
            ->select('menu_id', 'is_available')
            ->where('restaurant_branch_id', $restaurantBranch->id)
            ->get();

        $menus = Menu::search($request->filter)
            ->whereIn('id', $availableMenus->pluck('menu_id')->toArray())
            ->take(1000)
            ->get();

        $this->optimizeMenus($menus);

        $menus = $menus->map(function ($menu) use ($availableMenus) {
            $menuId = $menu->id;
            $menu = $menu->toArray();
            $menu['is_available'] = boolval($availableMenus->firstWhere('menu_id', $menuId)->is_available);
            return $menu;
        });

        return $menus;
    }

    public function getMenusByCategory(Request $request, RestaurantCategory $restaurantCategory)
    {
        $menus = Menu::search($request->filter)->where('restaurant_category_id', $restaurantCategory->id)->paginate(10);
        return CollectionHelper::removePaginateLinks($menus);
    }

    public function getMenusByBranchWithAdditionals(Request $request, RestaurantBranch $restaurantBranch)
    {
        $availableMenus = DB::table('restaurant_branch_menu_map')
            ->select('menu_id', 'is_available')
            ->where('restaurant_branch_id', $restaurantBranch->id)
            ->get();

        $menus = Menu::search($request->filter)
            ->whereIn('id', $availableMenus->pluck('menu_id')->toArray())
            ->paginate(10);

        $this->optimizeMenus($menus);
        $menus = CollectionHelper::removePaginateLinks($menus);

        $menus['data'] = array_map(function ($menu) use ($availableMenus) {
            $menu->load(['menuToppings', 'menuOptions', 'menuOptions.options']);
            $menuId = $menu->id;
            $menu = $menu->toArray();
            $menu['is_available'] = boolval($availableMenus->firstWhere('menu_id', $menuId)->is_available);
            return $menu;
        }, $menus['data']);

        return $menus;
    }

    public function updateSearchIndex(Request $request, Menu $menu)
    {
        $validatedData = $request->validate([
            'search_index' => 'required|numeric',
        ]);

        $menu->update($validatedData);
        CacheHelper::forgetCategoryIdsByBranchCache($menu->id);
        DataChanged::dispatch($this->user, 'update', 'menus', $menu->slug, $request->url(), 'success', $request->all());

        return response()->json($menu->load('restaurant', 'restaurantCategory'), 200);
    }

    public function updateVariants(Request $request, Menu $menu)
    {
        $validatedData = $request->validate([
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string',
            'variants.*.values' => 'required|array',

            'menu_variants' => 'required',
            'menu_variants.*.slug' => 'nullable|exists:App\Models\MenuVariant,slug',
            'menu_variants.*.variant' => 'required',
            'menu_variants.*.price' => 'required|numeric',
            'menu_variants.*.tax' => 'required|numeric',
            'menu_variants.*.discount' => 'required|numeric',
            'menu_variants.*.is_enable' => 'required|boolean',
            'menu_variants.*.image_slug' => 'nullable|exists:App\Models\File,slug',
        ]);

        if (isset($validatedData['variants'])) {
            $menu->update([
                'variants' => $validatedData['variants'],
            ]);

            DataChanged::dispatch($this->user, 'update', 'menus', $menu->slug, $request->url(), 'success', $validatedData['variants']);
        }

        $variantSlugs = $menu->menuVariants->pluck('slug');

        foreach ($validatedData['menu_variants'] as $data) {
            if (isset($data['slug']) && $variantSlugs->contains($data['slug'])) {
                $menuVariant = MenuVariant::where('slug', $data['slug'])->first();
                $menuVariant->update($data);

                $arrKey = $variantSlugs->search($data['slug']);
                unset($variantSlugs[$arrKey]);

                DataChanged::dispatch($this->user, 'update', 'menu_variants', $data['slug'], $request->url(), 'success', $data);
            } else {
                $data['menu_id'] = $menu->id;
                $data['slug'] = $this->generateUniqueSlug();

                MenuVariant::create($data);
                DataChanged::dispatch($this->user, 'create', 'menu_variants', $data['slug'], $request->url(), 'success', $data);
            }

            if (isset($data['image_slug'])) {
                $this->updateFile($data['image_slug'], 'menu_variants', $data['slug']);
            }
        }

        foreach ($variantSlugs as $slug) {
            $menuVariant = MenuVariant::where('slug', $slug)->first();
            $menuVariant->delete();
        }

        return response()->json($menu->refresh()->load('menuVariants'), 200);
    }

    private function optimizeMenus($menus)
    {
        $menus->load([
            'restaurant' => function ($query) {
                $query->select('id', 'slug', 'name');
            },
            'restaurantCategory' => function ($query) {
                $query->select('id', 'slug', 'name');
            },
        ]);

        foreach ($menus as $menu) {
            $menu->makeHidden(['variants', 'created_by', 'updated_by']);
            $menu->restaurantCategory->makeHidden(['created_by', 'updated_by', 'images', 'covers']);
            $menu->restaurant->makeHidden(['is_enable', 'commission', 'rating', 'first_order_date', 'created_by', 'updated_by', 'images', 'covers']);

            $menu->menu_variants = $menu->menuVariants()
                ->select('slug', 'price', 'discount')
                ->where('is_enable', 1)
                ->orderBy('price', 'asc')
                ->limit(1)
                ->get();
        }
    }
}
