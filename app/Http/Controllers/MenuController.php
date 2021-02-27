<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use App\Models\MenuVariation;
use App\Models\MenuVariationValue;
use App\Models\MenuTopping;
use App\Models\MenuToppingValue;

class MenuController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Menu::with('restaurant')
            ->with('menuVariations')
            ->with('menuToppings')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
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

        $validatedData = $request->validate($this->getParamsToValidate(true));

        $validatedData['restaurant_id'] = $this->getRestaurantId($request->restaurant_slug);
        $validatedData['restaurant_category_id'] = $this->getRestaurantCategoryId($request->restaurant_category_slug);

        $menu = Menu::create($validatedData);
        $menuId = $menu->id;

        $this->createVariations($menuId, $validatedData['menu_variations']);
        $this->createToppings($menuId, $validatedData['menu_toppings']);

        return response()->json($menu->refresh()->load('menuVariations', 'menuToppings', 'menuVariations.menuVariationValues', 'menuToppings.menuToppingValues'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $menu = Menu::with('restaurant')->where('slug', $slug)->firstOrFail();
        return response()->json($menu, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $menu = Menu::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());

        $validatedData['restaurant_id'] = $this->getRestaurantId($request->restaurant_slug);
        $validatedData['restaurant_category_id'] = $this->getRestaurantCategoryId($request->restaurant_category_slug);

        $menu->update($validatedData);
        return response()->json($menu->load('restaurant'), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Menu::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getMenusByRestaurant(Request $request, $slug)
    {
        $menus = Restaurant::where('slug', $slug)->firstOrFail()->menus();
        return $menus->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
        // return Menu::whereHas('restaurant', function ($q) use ($slug, $request) {
        //     $q->where('slug', $slug);
        // })->where('name', 'LIKE', '%' . $request->filter . '%')
        // ->paginate(10);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'name' => 'required',
            'name_mm' => 'required',
            'description' => 'required',
            'description_mm' => 'required',
            'price' => 'required|numeric',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'restaurant_category_slug' => 'required|exists:App\Models\RestaurantCategory,slug',
            'menu_variations' => 'required|array',
            'menu_variations.*.name' => 'required|string',
            'menu_variations.*.description' => 'required|string',
            'menu_toppings' => 'required|array',
            'menu_toppings.*.name' => 'required|string',
            'menu_toppings.*.description' => 'required|string',

            'menu_variations.*.menu_variation_values' => 'required|array',
            'menu_variations.*.menu_variation_values.*.name' => 'required|string',
            'menu_variations.*.menu_variation_values.*.value' => 'required|string',
            'menu_variations.*.menu_variation_values.*.price' => 'required|numeric',

            'menu_toppings.*.menu_topping_values' => 'required|array',
            'menu_toppings.*.menu_topping_values.*.name' => 'required|string',
            'menu_toppings.*.menu_topping_values.*.value' => 'required|string',
            'menu_toppings.*.menu_topping_values.*.price' => 'required|numeric',

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
            $menuTopping = MenuTopping::create($topping);
            $toppingId = $menuTopping->id;
            $this->createToppingValues($toppingId, $topping['menu_topping_values']);
        }
    }

    private function createToppingValues($toppingId, $toppingValues)
    {
        foreach ($toppingValues as $toppingValue) {
            $toppingValue['slug'] = $this->generateUniqueSlug();
            $toppingValue['menu_topping_id'] = $toppingId;
            MenuToppingValue::create($toppingValue);
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
}
