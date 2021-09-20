<?php

namespace App\Http\Controllers\Admin\v3;

use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuOption;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class MenuOptionController extends Controller
{
    use ResponseHelper, StringHelper;

    public function index(Menu $menu)
    {
        return MenuOption::with('options')->where('menu_id', $menu->id)->get();
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();
        $validatedData = $this->validateMenuOption($request);

        try {
            $menuOption = MenuOption::create($validatedData);
            return response()->json($menuOption, 201);
        } catch (QueryException $e) {
            return $this->generateResponse('There is already same option name for this menu.', 409, true);
        }
    }

    public function show(MenuOption $menuOption)
    {
        return $menuOption->load('options');
    }

    public function update(Request $request, MenuOption $menuOption)
    {
        $validatedData = $this->validateMenuOption($request);

        try {
            $menuOption->update($validatedData);
            return response()->json($menuOption, 200);
        } catch (QueryException $e) {
            return $this->generateResponse('There is already same option name for this menu.', 409, true);
        }
    }

    public function destroy(MenuOption $menuOption)
    {
        $menuOption->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function validateMenuOption($request)
    {
        $rules = [
            'name' => 'required|string',
            'max_choice' => 'required|integer',
            'menu_slug' => 'required|exists:App\Models\Menu,slug',
        ];

        if ($request->slug) {
            $rules['slug'] = 'required|unique:menu_options';
        }

        $validatedData = $request->validate($rules);
        $validatedData['menu_id'] = $this->getMenuIdBySlug($validatedData['menu_slug']);

        return $validatedData;
    }

    private function getMenuIdBySlug($slug)
    {
        return Menu::where('slug', $slug)->value('id');
    }
}
