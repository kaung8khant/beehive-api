<?php

namespace App\Http\Controllers;

use App\Models\MenuToppingValue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;

class MenuToppingValueController extends Controller
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

        return MenuToppingValue::
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

        $menuToppingValue = MenuToppingValue::create($request->validate([
            'name' => 'required|unique:menu_Topping_values',
            'slug' => 'required|unique:menu_Topping_values',
            'value' => 'required|unique:menu_Topping_values',
            'price' => 'required',
            'menu_topping_id' => 'required|exists:App\Models\MenuTopping,id',
        ]));

        return response()->json($menuToppingValue, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuToppingValue  $menuToppingValue
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(MenuToppingValue::with('menu_toppings')->where('slug', $slug)->firstOrFail(), 200);
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
    public function update(Request $request, $slug)
    {
        $menuToppingValue = MenuToppingValue::where('slug', $slug)->firstOrFail();

        $menuToppingValue->update($request->validate([
            'name'=>'required|unique:menu_topping_values',
            'price'=>'required',
            'value'=>'required|unique:menu_topping_values',
            'menu_topping_id' => 'required|exists:App\Models\MenuTopping,id',
            Rule::unique('menu_topping_values')->ignore($menuToppingValue->id),
        ]));

        return response()->json($menuToppingValue, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuToppingValue  $menuToppingValue
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        MenuToppingValue::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
