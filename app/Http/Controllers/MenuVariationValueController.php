<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\MenuVariationValue;
use App\Models\MenuVariation;

class MenuVariationValueController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return MenuVariationValue::with('menuVariation')
            ->where('value', 'LIKE', '%' . $request->filter . '%')
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

        $validatedData = $request->validate($this->getParamsToValidate(TRUE));
        $validatedData['menu_variation_id'] = $this->getMenuVariationId($request->menu_variation_slug);

        $menuVariationValue = MenuVariationValue::create($validatedData);
        return response()->json($menuVariationValue->load('menuVariation'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuVariationValue  $menuVariationValue
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $menuVariationValue = MenuVariationValue::with('menuVariation')->where('slug', $slug)->firstOrFail();
        return response()->json($menuVariationValue, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuVariationValue  $menuVariationValue
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $menuVariationValue = MenuVariationValue::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['menu_variation_id'] = $this->getMenuVariationId($request->menu_variation_slug);

        $menuVariationValue->update($validatedData);
        return response()->json($menuVariationValue->load('menuVariation'), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuVariationValue  $menuVariationValue
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        MenuVariationValue::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = FALSE)
    {
        $params = [
            'value' => 'required|string',
            'price' => 'required|numeric',
            'menu_variation_slug' => 'required|exists:App\Models\MenuVariation,slug',
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
