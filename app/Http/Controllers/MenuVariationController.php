<?php

namespace App\Http\Controllers;

use App\Models\MenuVariation;
use Illuminate\Http\Request;

class MenuVariationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return MenuVariation::with('menu')->paginate(10);
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

        $menuVariation = MenuVariation::create($request->validate([
            'name' => 'required|unique:menu_variations',
            'slug' => 'required|unique:menu_variations',
            'menu_id' => 'required|exists:App\Models\Menu,id',
        ]));

        return response()->json($menu, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    public function show(MenuVariation $menuVariation)
    {
        return MenuVariation::with('menu')->where('slug', $slug)->firstOrFail();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    public function edit(MenuVariation $menuVariation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MenuVariation $menuVariation)
    {
        $menuVariation = MenuVariation::where('slug', $slug)->firstOrFail();

        $menuVariation->update($request->validate([
            'name' => [
                'required',
                Rule::unique('menu_variations')->ignore($menuVariation->id),
            ],
             'menu_id' => 'required|exists:App\Models\Menu,id',
        ]));

        return response()->json($menuVariation, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    public function destroy(MenuVariation $menuVariation)
    {
        MenuVariation::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
