<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\MenuVariation;
use App\Models\Menu;

class MenuVariationController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return MenuVariation::with('menu')
            ->with('menuVariationValues')
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
    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(TRUE));
        $validatedData['menu_id'] = $this->getMenuId($request->menu_slug);

        $menuVariation = MenuVariation::create($validatedData);
        return response()->json($menuVariation->load('menu'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $menu = MenuVariation::with('menu')->with('menuVariationValues')->where('slug', $slug)->firstOrFail();
        return response()->json($menu, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $menuVariation = MenuVariation::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['menu_id'] = $this->getMenuId($request->menu_slug);

        $menuVariation->update($validatedData);
        return response()->json($menuVariation->load('menu'), 200);
    }

    public function getVariationsByMenu(Request $request, $slug)
    {
        return MenuVariation::with('menuVariationValues')->whereHas('menu', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        MenuVariation::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = FALSE)
    {
        $params = [
            'name' => 'required|string',
            'name_mm' => 'nullable|string',
            'menu_slug' => 'required|exists:App\Models\Menu,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:menu_variations';
        }

        return $params;
    }

    private function getMenuId($slug)
    {
        return Menu::where('slug', $slug)->first()->id;
    }
}
