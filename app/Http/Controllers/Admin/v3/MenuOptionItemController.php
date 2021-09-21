<?php

namespace App\Http\Controllers\Admin\v3;

use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\MenuOption;
use App\Models\MenuOptionItem;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class MenuOptionItemController extends Controller
{
    use ResponseHelper, StringHelper;

    public function index(MenuOption $menuOption)
    {
        return MenuOptionItem::where('menu_option_id', $menuOption->id)->get();
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();
        $validatedData = $this->validateMenuOptionItem($request);

        try {
            $menuOptionItem = MenuOptionItem::create($validatedData);
            return response()->json($menuOptionItem, 201);
        } catch (QueryException $e) {
            return $this->generateResponse('There is already same name for this option.', 409, true);
        }
    }

    public function show(MenuOptionItem $menuOptionItem)
    {
        return $menuOptionItem->load('menuOption');
    }

    public function update(Request $request, MenuOptionItem $menuOptionItem)
    {
        $validatedData = $this->validateMenuOptionItem($request);

        try {
            $menuOptionItem->update($validatedData);
            return response()->json($menuOptionItem, 200);
        } catch (QueryException $e) {
            return $this->generateResponse('There is already same name for this option.', 409, true);
        }
    }

    public function destroy(MenuOptionItem $menuOptionItem)
    {
        $menuOptionItem->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function validateMenuOptionItem($request)
    {
        $rules = [
            'name' => 'required|string',
            'price' => 'required|numeric',
            'menu_option_slug' => 'required|exists:App\Models\MenuOption,slug',
        ];

        if ($request->slug) {
            $rules['slug'] = 'required|unique:menu_option_items';
        }

        $validatedData = $request->validate($rules);
        $validatedData['menu_option_id'] = $this->getMenuOptionIdBySlug($validatedData['menu_option_slug']);

        return $validatedData;
    }

    private function getMenuOptionIdBySlug($slug)
    {
        return MenuOption::where('slug', $slug)->value('id');
    }
}
