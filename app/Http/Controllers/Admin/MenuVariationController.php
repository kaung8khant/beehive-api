<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuVariation;
use App\Models\MenuVariationValue;
use Illuminate\Http\Request;

class MenuVariationController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        return MenuVariation::with('menu', 'menuVariationValues')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'menu_slug' => 'required|exists:App\Models\Menu,slug',
            'menu_variations.*.name' => 'required|string',
            'menu_variations.*.menu_variation_values' => 'required|array',
            'menu_variations.*.menu_variation_values.*.value' => 'required|string',
            'menu_variations.*.menu_variation_values.*.price' => 'required|numeric',
            'menu_variations.*.menu_variation_values.*.image_slug' => 'nullable|exists:App\Models\File,slug',
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

                if (!empty($menuVariationValue['image_slug'])) {
                    $this->updateFile($menuVariationValue['image_slug'], 'menu_variation_values', $menuVariationValue['slug']);
                }
            }
        }

        return response()->json(['message' => 'Successfully Created.'], 201);
    }

    public function show(MenuVariation $menuVariation)
    {
        return response()->json($menuVariation->load('menu', 'menuVariationValues'), 200);
    }

    public function update(Request $request, MenuVariation $menuVariation)
    {
        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['menu_id'] = $this->getMenu($request->menu_slug)->id;
        $menuVariation->update($validatedData);

        return response()->json($menuVariation->load('menuVariationValues'), 200);
    }

    public function getVariationsByMenu(Request $request, Menu $menu)
    {
        return MenuVariation::with('menuVariationValues')
            ->where('menu_id', $menu->id)
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->paginate(10);
    }

    public function destroy(MenuVariation $menuVariation)
    {
        $menuVariation->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'name' => 'required|string',
            'menu_slug' => 'required|exists:App\Models\Menu,slug',
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
