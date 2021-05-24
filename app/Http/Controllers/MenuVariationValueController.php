<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\MenuVariation;
use App\Models\MenuVariationValue;
use Illuminate\Http\Request;

class MenuVariationValueController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        return MenuVariationValue::with('menuVariation')
            ->where('value', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(true));
        $validatedData['menu_variation_id'] = $this->getMenuVariationId($request->menu_variation_slug);

        $menuVariationValue = MenuVariationValue::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'menu_variation_values', $menuVariationValue->slug);
        }

        return response()->json($menuVariationValue->load('menuVariation'), 201);
    }

    public function show(MenuVariationValue $menuVariationValue)
    {
        return response()->json($menuVariationValue->load('menuVariation'), 200);
    }

    public function update(Request $request, MenuVariationValue $menuVariationValue)
    {
        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['menu_variation_id'] = $this->getMenuVariationId($request->menu_variation_slug);
        $menuVariationValue->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'menu_variation_values', $menuVariationValue->slug);
        }

        return response()->json($menuVariationValue->load('menuVariation'), 200);
    }

    public function destroy(MenuVariationValue $menuVariationValue)
    {
        foreach ($menuVariationValue->images as $image) {
            $this->deleteFile($image->slug);
        }

        $menuVariationValue->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'value' => 'required|string',
            'price' => 'required|numeric',
            'menu_variation_slug' => 'required|exists:App\Models\MenuVariation,slug',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:menu_variation_values';
        }

        return $params;
    }

    private function getMenuVariationId($slug)
    {
        return MenuVariation::where('slug', $slug)->first()->id;
    }
}
