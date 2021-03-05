<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\MenuVariation;
use App\Models\Menu;
use App\Models\MenuVariationValue;

class MenuVariationController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return MenuVariation::with('menu')
            ->with('menuVariationValues')
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
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'menu_slug' => 'required|exists:App\Models\Menu,slug',
            'menu_variations.*.name' => 'required|string',
            'menu_variations.*.name_mm' => 'nullable|string',
            'menu_variations.*.menu_variation_values' => 'required|array',
            'menu_variations.*.menu_variation_values.*.value' => 'required|string',
            'menu_variations.*.menu_variation_values.*.price' => 'required|numeric',
        ]);

        $menu = $this->getMenu($validatedData['menu_slug']);

        foreach ($validatedData['menu_variations'] as $menuVariation) {
            $menuVariation['slug'] = $this->generateUniqueSlug();
            $menuVariation['menu_id'] = $menu->id;

            $menuVariationId = MenuVariation::create($menuVariation)->id;

            foreach ($menuVariation['menu_variation_values'] as $menuVariationValue) {

                $menuVariationValue['slug'] = $this->generateUniqueSlug();
                $menuVariationValue['menu_variation_id'] = $menuVariationId;

                MenuVariationValue::create($menuVariationValue);
            }
        }

        $menuVariation = MenuVariation::where('menu_id', $menu->slug);

        return response()->json(['message' => 'Successfully Created.'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $menu = MenuVariation::with('menu')->with('menuVariationValues')->where('slug', $slug)->firstOrFail();
        return response()->json($menu, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $menuVariation = MenuVariation::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['menu_id'] = $this->getMenu($request->menu_slug)->id;

        $menuVariation->update($validatedData);

        $menuVariationId = $menuVariation->id;

        $menuVariation->menuVariationValues()->delete();

        $this->createVariationValues($menuVariationId, $validatedData['menu_variation_values']);

        // return response()->json($menuVariation->load('menuVariationValues'), 200);
        return response()->json(['message' => 'Successfully Updated.'], 201);
    }

    public function getVariationsByMenu(Request $request, $slug)
    {
        return MenuVariation::with('menuVariationValues')->whereHas('menu', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        MenuVariation::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function createVariationValues($variationId, $variationValues)
    {
        foreach ($variationValues as $variationValue) {
            $variationValue['slug'] = $this->generateUniqueSlug();
            $variationValue['menu_variation_id'] = $variationId;
            MenuVariationValue::create($variationValue);
        }
    }

    private function getParamsToValidate($slug = FALSE)
    {
        $params = [
            'name' => 'required|string',
            'name_mm' => 'nullable|string',
            'menu_slug' => 'required|exists:App\Models\Menu,slug',
            'menu_variation_values' => 'required|array',
            'menu_variation_values.*.value' => 'required|string',
            'menu_variation_values.*.price' => 'required|numeric',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:menu_variations';
        }

        return $params;
    }

    private function getMenu($slug)
    {
        return Menu::where('slug', $slug)->first();
    }
}
