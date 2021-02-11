<?php

namespace App\Http\Controllers;

use App\Models\MenuTopping;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;

class MenuToppingController extends Controller
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

        return MenuTopping::
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

        $menuTopping = MenuTopping::create($request->validate([
            'name' => 'required|unique:menu_toppings',
            'description' => 'required',
            'slug' => 'required|unique:menu_toppings',
            'menu_id' => 'required|exists:App\Models\Menu,id',
        ]));

        return response()->json($menuTopping, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuTopping  $menuTopping
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(MneuTopping::with('menu')->where('slug', $slug)->firstOrFail(), 200);
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
    public function update(Request $request, $slug)
    {
        $menuTopping = MenuTopping::where('slug', $slug)->firstOrFail();

        $menuTopping->update($request->validate([
            'name'=>'required|unique:menu_toppings',
            'description'=>'required',
            'menu_id' => 'required|exists:App\Models\Menu,id',
            Rule::unique('menu_toppings')->ignore($menuTopping->id),
        ]));

        return response()->json($menuTopping, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuTopping  $menuTopping
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        MenuTopping::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
