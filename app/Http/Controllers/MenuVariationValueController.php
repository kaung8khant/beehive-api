<?php

namespace App\Http\Controllers;

use App\Models\MenuVariationValue;
use Illuminate\Http\Request;

class MenuVariationValueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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

        $menuVariationValue = MenuVariationValue::create($request->validate([
            'name' => 'required|unique:menu_variation_values',
            'slug' => 'required|unique:menu_variation_values',
            'value' => 'required|unique:menu_variation_values',
            'price' => 'required|unique:menu_variation_values',
            'menu_variation_id' => 'required|exists:App\Models\MenuVariation,id',
        ]));

        return response()->json($menu, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuVariationValue  $menuVariationValue
     * @return \Illuminate\Http\Response
     */
    public function show(MenuVariationValue $menuVariationValue)
    {
        return MenuVariationValue::with('menu_variations')->where('slug', $slug)->firstOrFail();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MenuVariationValue  $menuVariationValue
     * @return \Illuminate\Http\Response
     */
    public function edit(MenuVariationValue $menuVariationValue)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuVariationValue  $menuVariationValue
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MenuVariationValue $menuVariationValue)
    {
        $menuVariationValue = MenuVariationValue::where('slug', $slug)->firstOrFail();

        $menuVariationValue->update($request->validate([
            'name' => [
                'required',
                Rule::unique('menu_variation_values')->ignore($menuVariationValue->id),
            ],
            'value' => [
                'required'
            ],
            'price' => [
                'required'
            ],
             'menu_variation_id' => 'required|exists:App\Models\MenuVariation,id',
        ]));

        return response()->json($menuVariationValue, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuVariationValue  $menuVariationValue
     * @return \Illuminate\Http\Response
     */
    public function destroy(MenuVariationValue $menuVariationValue)
    {
        //
    }
}
