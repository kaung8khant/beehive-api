<?php

namespace App\Http\Controllers;

use App\Models\MenuToppingValue;
use Illuminate\Http\Request;

class MenuToppingValueController extends Controller
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

        $menuToppingValue = MenuToppingValue::create($request->validate([
            'name' => 'required|unique:menu_Topping_values',
            'slug' => 'required|unique:menu_Topping_values',
            'value' => 'required|unique:menu_Topping_values',
            'price' => 'required|unique:menu_Topping_values',
            'menu_topping_id' => 'required|exists:App\Models\MenuTopping,id',
        ]));

        return response()->json($menu, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuToppingValue  $menuToppingValue
     * @return \Illuminate\Http\Response
     */
    public function show(MenuToppingValue $menuToppingValue)
    {
        return MenuToppingValue::with('menu_toppings')->where('slug', $slug)->firstOrFail();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MenuToppingValue  $menuToppingValue
     * @return \Illuminate\Http\Response
     */
    public function edit(MenuToppingValue $menuToppingValue)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuToppingValue  $menuToppingValue
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MenuToppingValue $menuToppingValue)
    {
        $menuToppingValue = MenuTopppingValue::where('slug', $slug)->firstOrFail();

        $menuToppingValue->update($request->validate([
            'name' => [
                'required',
                Rule::unique('menu_topping_values')->ignore($menuToppingValue->id),
            ],
            'value' => [
                'required'
            ],
            'price' => [
                'required'
            ],
             'menu_topping_id' => 'required|exists:App\Models\MenuTopping,id',
        ]));

        return response()->json($menuToppingValue, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuToppingValue  $menuToppingValue
     * @return \Illuminate\Http\Response
     */
    public function destroy(MenuToppingValue $menuToppingValue)
    {
        MenuToppingValue::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
