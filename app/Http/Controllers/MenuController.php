<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Models\File;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use App\Models\MenuVariation;
use App\Models\MenuVariationValue;
use App\Models\MenuTopping;
use App\Models\RestaurantBranch;

class MenuController extends Controller
{
    use StringHelper, FileHelper;

    use ResponseHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/menus",
     *      operationId="getMenuLists",
     *      tags={"Menus"},
     *      summary="Get list of menus",
     *      description="Returns list of menus",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="filter",
     *          description="Filter",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function index(Request $request)
    {
        return Menu::with('restaurant')
            ->with('restaurantCategory')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Post(
     *      path="/api/v2/admin/menus",
     *      operationId="storeMenu",
     *      tags={"Menus"},
     *      summary="Create a menu",
     *      description="Returns newly created menu variation",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created menu",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Menu")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(true));
        $restaurant = Restaurant::where('slug', $request->restaurant_slug)->firstOrFail();
        $validatedData['restaurant_id'] = $restaurant->id;
        $validatedData['restaurant_category_id'] = $this->getRestaurantCategoryId($request->restaurant_category_slug);

        $menu = Menu::create($validatedData);
        $menuId = $menu->id;

        $this->updateFile($request->image_slug, 'menus', $menu->slug);

        $this->createVariations($menuId, $validatedData['menu_variations']);
        $this->createToppings($menuId, $validatedData['menu_toppings']);
        foreach ($restaurant->restaurantBranches as $branch) {
            $availableMenus = Menu::where('slug', $menu->slug)->pluck('id');
            $branch->availableMenus()->attach($availableMenus);
        }
        return response()->json($menu->load('restaurant'), 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/menus/{slug}",
     *      operationId="showMenu",
     *      tags={"Menus"},
     *      summary="Get One menu",
     *      description="Returns a requested menu ",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested menu",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function show($slug)
    {
        $menu = Menu::with('restaurant')->with('restaurantCategory')
            ->with('menuVariations')->with('menuVariations.menuVariationValues')
            ->with('menuToppings')
            ->where('slug', $slug)->firstOrFail();
        return response()->json($menu, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Put(
     *      path="/api/v2/admin/menus/{slug}",
     *      operationId="updateMenu",
     *      tags={"Menus"},
     *      summary="Update a menu",
     *      description="Update a requested menu ",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a menu ",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New menu data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Menu"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function update(Request $request, $slug)
    {
        $menu = Menu::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());

        $validatedData['restaurant_id'] = $this->getRestaurantId($request->restaurant_slug);
        $validatedData['restaurant_category_id'] = $this->getRestaurantCategoryId($request->restaurant_category_slug);

        $menu->update($validatedData);

        $menuId = $menu->id;

        if ($menu->images === []) {
            $this->updateFile($request->image_slug, 'menus', $slug);
        } else {
            foreach ($menu->images as $image) {
                $this->deleteFile($$image->slug);
                $this->updateFile($request->image_slug, 'menus', $slug);
            }
        }

        if ($request->menu_variations) {
            $menu->menuVariations()->delete();
            $this->createVariations($menuId, $validatedData['menu_variations']);
        }

        if ($request->menu_toppings) {
            $menu->menuToppings()->delete();
            $this->createToppings($menuId, $validatedData['menu_toppings']);
        }
        return response()->json($menu->load('restaurant'), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Delete(
     *      path="/api/v2/admin/menus/{slug}",
     *      operationId="deleteMenu",
     *      tags={"Menus"},
     *      summary="Delete One Menu",
     *      description="Delete one specific menu",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested menu",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function destroy($slug)
    {
        $menu = Menu::where('slug', $slug)->firstOrFail();

        foreach ($menu->images as $image) {
            $this->deleteFile($image->slug);
        }

        $menu->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     *  Display a listing of the menus by one restaurant.
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurants/{slug}/menus",
     *      operationId="getMenusByRestaurant",
     *      tags={"Menus"},
     *      summary="Get Menus By Restaurant",
     *      description="Returns list of menus",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested restaurant",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="filter",
     *          description="Filter",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function getMenusByRestaurant(Request $request, $slug)
    {
        return Menu::with('restaurantCategory')->whereHas('restaurant', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    /**
     *  Display a available menus by one restaurant branch.
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurant-branches/{slug}/menus",
     *      operationId="getAvailableMenusByRestaurantBranch",
     *      tags={"Menus"},
     *      summary="Get Available Menus By Restaurant Branch",
     *      description="Returns list of menus",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested restaurant branch",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="filter",
     *          description="Filter",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function getMenusByBranch(Request $request, $slug)
    {
        $branch = RestaurantBranch::with('availableMenus')
        ->where('slug', $slug)
        ->firstOrFail();

        $menus = $branch->availableMenus()->with('restaurantCategory')
        ->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })
        ->paginate(10);

        foreach ($menus as $menu) {
            $menu->setAppends(['is_available']);
            $menu['images']=File::where('source', 'menus')
            ->where('source_id', $menu->id)
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
        }
        return $menus;
    }

    public function getAvailableMenusByBranch(Request $request, $slug)
    {
        $branch = RestaurantBranch::with('availableMenus')->with('availableMenus.images')
            ->where('slug', $slug)
            ->firstOrFail();

        $availableMenus = $branch->availableMenus()->with('restaurantCategory')
            ->with('menuVariations')->with('menuVariations.menuVariationValues')
            ->with('menuToppings')->where('is_available', true)
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->paginate(10);
        return $availableMenus;
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'name' => 'required',
            'description' => 'required',
            'description_mm' => 'nullable',
            'price' => 'required|numeric',
            'is_enable' => 'required|boolean',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'restaurant_category_slug' => 'required|exists:App\Models\RestaurantCategory,slug',
            'menu_variations' => 'nullable|array',
            'menu_variations.*.name' => 'required|string',
            'menu_toppings' => 'nullable|array',
            'menu_toppings.*.name' => 'required|string',
            'menu_toppings.*.price' => 'required|numeric',
            'menu_variations.*.menu_variation_values' => 'required|array',
            'menu_variations.*.menu_variation_values.*.value' => 'required|string',
            'menu_variations.*.menu_variation_values.*.price' => 'required|numeric',
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
            $menuVariation =  MenuVariation::create($variation);
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
        }
    }

    private function createToppings($menuId, $toppings)
    {
        foreach ($toppings as $topping) {
            $topping['slug'] = $this->generateUniqueSlug();
            $topping['menu_id'] = $menuId;
            MenuTopping::create($topping);
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

    /**
     * @OA\Patch(
     *      path="/api/v2/admin/menus/toggle-enable/{slug}",
     *      operationId="enableMenu",
     *      tags={"Menus"},
     *      summary="Enable Menu",
     *      description="Enable a menu",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the Menu",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function toggleEnable($slug)
    {
        $menu = Menu::where('slug', $slug)->firstOrFail();
        $menu->is_enable = !$menu->is_enable;
        $menu->save();
        return response()->json(['message' => 'Success.'], 200);
    }
}
