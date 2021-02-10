<?php

namespace App\Http\Controllers;

use App\Models\MenuVariationValue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;

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
        $filter= $request->filter;

        return MenuVariationValue::
        where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('slug', $filter)->paginate(10);
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
            'price' => 'required',
            'menu_variation_id' => 'required|exists:App\Models\MenuVariation,id',
        ]));

        return response()->json($menuVariationValue, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuVariationValue  $menuVariationValue
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(MenuVariationValue::with('menu_variations')->where('slug', $slug)->firstOrFail(), 200);
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
    public function update(Request $request, $slug)
    {
        $menuVariationValue = MenuVariationValue::where('slug', $slug)->firstOrFail();

        $menuVariationValue->update($request->validate([
            'name'=>'required|unique:menu_variation_values',
            // 'slug' => 'required|unique:menu_variation_values',
            'price'=>'required',
            'value'=>'required|unique:menu_variation_values',
            'menu_variation_id' => 'required|exists:App\Models\MenuVariation,id',
            Rule::unique('menu_variations_values')->ignore($menuVariationValue->id),
        ]));

        return response()->json($menuVariationValue, 200);
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
}
