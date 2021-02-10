<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;

class MenuController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Menu::paginate(10);
        // $filter= $request->filter;

        // return Menu::with('restaurants')
        // ->where('name', 'LIKE', '%' . $filter . '%')
        // ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        // ->orWhere('slug', $filter)->paginate(10);
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

        $menu = Menu::create($request->validate([
            'name' => 'required|unique:menus',
            'name_mm' => 'required|unique:menus',
            'description' => 'required|unique:menus',
            'description_mm' => 'required|unique:menus',
            'price' => 'required|unique:menus',
            'slug' => 'required|unique:menus',
            // 'restaurant_id' => 'required|exists:App\Models\Restaurant,id',
            // 'restaurantCategoy_id' => 'required|exists:App\Models\RestaurantCategory,id',
        ]));

        return response()->json($menu, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function show(Menu $menu)
    {
        // return Menu::with('restaurant')->where('slug', $slug)->firstOrFail();
        return response()->json(Menu::where('slug', $slug)->firstOrFail(), 200);
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function edit(Menu $menu)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $menu = Menu::where('slug', $slug)->firstOrFail();

        $menu->update($request->validate([
            'name'=>'required',
            'name_mm'=>'required',
            'description'=>'required',
            'description_mm'=>'required',
            // 'restaurant_id' => 'required|exists:App\Models\Restaurant,id',
            // 'restaurantCategory_id' => 'required|exists:App\Models\RestaurantCategory,id',
            Rule::unique('menus')->ignore($menu->id),
        ]));

        return response()->json($menu, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Menu::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
