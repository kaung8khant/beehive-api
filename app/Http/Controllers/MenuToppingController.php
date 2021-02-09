<?php

namespace App\Http\Controllers;

use App\Models\MenuTopping;
use Illuminate\Http\Request;

class MenuToppingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return MenuTopping::with('menu')->paginate(10);
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

        $menuVariation = MenuTopping::create($request->validate([
            'name' => 'required|unique:menu_toppings',
            'slug' => 'required|unique:menu_toppings',
            'menu_id' => 'required|exists:App\Models\Menu,id',
        ]));

        return response()->json($menu, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuTopping  $menuTopping
     * @return \Illuminate\Http\Response
     */
    public function show(MenuTopping $menuTopping)
    {
        return MenuTopping::with('menu')->where('slug', $slug)->firstOrFail();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MenuTopping  $menuTopping
     * @return \Illuminate\Http\Response
     */
    public function edit(MenuTopping $menuTopping)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuTopping  $menuTopping
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MenuTopping $menuTopping)
    {
        $menuTopping = MenuVariation::where('slug', $slug)->firstOrFail();

        $menuTopping->update($request->validate([
            'name' => [
                'required',
                Rule::unique('menu_toppings')->ignore($menuTopping->id),
            ],
             'menu_id' => 'required|exists:App\Models\Menu,id',
        ]));

        return response()->json($menuTopping, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuTopping  $menuTopping
     * @return \Illuminate\Http\Response
     */
    public function destroy(MenuTopping $menuTopping)
    {
        MenuTopping::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
