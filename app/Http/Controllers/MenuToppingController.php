<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\MenuTopping;
use App\Models\MenuToppingValue;
use App\Models\Menu;

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
        return MenuTopping::with('menu')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function store(Request $request)
    // {
    //     $request['slug'] = $this->generateUniqueSlug();

    //     $validatedData = $request->validate($this->getParamsToValidate(true));
    //     $validatedData['menu_id'] = $this->getMenuId($request->menu_slug);

    //     $menuTopping = MenuTopping::create($validatedData);
    //     return response()->json($menuTopping->load('menu'), 201);
    // }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'menu_slug' => 'required|exists:App\Models\Menu,slug',
            'menu_toppings.*.name' => 'required|unique:menu_toppings',
            'menu_toppings.*.name_mm' => 'nullable|unique:menu_toppings',
            'menu_toppings.*.price' => 'required|numeric',

        ]);

        $menuId = $this->getMenuId($validatedData['menu_slug']);

        foreach ($validatedData['menu_toppings'] as $menuTopping) {
            $menuTopping['slug'] = $this->generateUniqueSlug();
            $menuTopping['menu_id'] = $menuId;

            MenuTopping::create($menuTopping)->id;
        }

        return response()->json(['message' => 'Successfully Created.'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuTopping  $menuTopping
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $menuTopping = MenuTopping::with('menu')->where('slug', $slug)->firstOrFail();
        return response()->json($menuTopping, 200);
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

        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['menu_id'] = $this->getMenuId($request->menu_slug);

        $menuTopping->update($validatedData);
        return response()->json($menuTopping->load('menu'), 200);
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

    public function getToppingsByMenu(Request $request, $slug)
    {
        return MenuTopping::whereHas('menu', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }


    private function getParamsToValidate($slug = false)
    {
        $params = [
            'name' => 'required|unique:menu_toppings',
            'name_mm' => 'nullable|unique:menu_toppings',
            'price' => 'required|numeric',
            'menu_slug' => 'required|exists:App\Models\Menu,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:menu_toppings';
        }

        return $params;
    }

    private function getMenuId($slug)
    {
        return Menu::where('slug', $slug)->first()->id;
    }
}
