<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\MenuToppingValue;
use App\Models\MenuTopping;

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
        return MenuToppingValue::with('menuTopping')
            ->where('value', 'LIKE', '%' . $request->filter . '%')
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
        $validatedData['menu_topping_id'] = $this->getMenuToppingId($request->menu_topping_slug);

        $menuToppingValue = MenuToppingValue::create($validatedData);
        return response()->json($menuToppingValue->load('menuTopping'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuToppingValue  $menuToppingValue
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $menuToppingValue = MenuToppingValue::with('menuTopping')->where('slug', $slug)->firstOrFail();
        return response()->json($menuToppingValue, 200);
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

        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['menu_topping_id'] = $this->getMenuToppingId($request->menu_topping_slug);

        $menuToppingValue->update($validatedData);
        return response()->json($menuToppingValue->load('menuTopping'), 200);
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

    private function getParamsToValidate($slug = FALSE)
    {
        $params = [
            'value' => 'required|string',
            'price' => 'required|numeric',
            'menu_topping_slug' => 'required|exists:App\Models\MenuTopping,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:menu_topping_values';
        }

        return $params;
    }

    private function getMenuToppingId($slug)
    {
        return MenuTopping::where('slug', $slug)->first()->id;
    }
}
