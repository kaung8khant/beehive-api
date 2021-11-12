<?php

namespace App\Http\Controllers\Admin\v3;

use App\Events\DataChanged;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\MenuOption;
use App\Models\MenuOptionItem;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuOptionItemController extends Controller
{
    use ResponseHelper, StringHelper;

    private $user;

    public function __construct()
    {
        if (Auth::guard('users')->check()) {
            $this->user = Auth::guard('users')->user();
        }
    }

    public function index(MenuOption $option)
    {
        return MenuOptionItem::exclude(['created_by', 'updated_by'])
            ->where('menu_option_id', $option->id)
            ->get();
    }

    public function store(Request $request, MenuOption $option)
    {
        $request['slug'] = $this->generateUniqueSlug();
        $validatedData = $this->validateMenuOptionItem($request);

        try {
            $validatedData['menu_option_id'] = $option->id;
            $menuOptionItem = MenuOptionItem::create($validatedData);

            DataChanged::dispatch($this->user, 'create', 'menu_option_items', $request->slug, $request->url(), 'success', $request->all());

            return response()->json($menuOptionItem, 201);
        } catch (QueryException $e) {
            return $this->generateResponse('There is already same item name for this option.', 409, true);
        }
    }

    public function show(MenuOption $option, MenuOptionItem $item)
    {
        return $item->makeHidden(['created_by', 'updated_by']);
    }

    public function update(Request $request, MenuOption $option, MenuOptionItem $item)
    {
        $validatedData = $this->validateMenuOptionItem($request);

        try {
            $validatedData['menu_option_id'] = $option->id;
            $item->update($validatedData);

            DataChanged::dispatch($this->user, 'update', 'menu_option_items', $item->slug, $request->url(), 'success', $request->all());

            return $item->makeHidden(['created_by', 'updated_by']);
        } catch (QueryException $e) {
            return $this->generateResponse('There is already same name for this option.', 409, true);
        }
    }

    public function destroy(Request $request, MenuOption $option, MenuOptionItem $item)
    {
        DataChanged::dispatch($this->user, 'delete', 'menu_option_items', $item->slug, $request->url(), 'success');
        $item->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function validateMenuOptionItem($request)
    {
        $rules = [
            'name' => 'required|string',
            'price' => 'required|numeric',
        ];

        if ($request->slug) {
            $rules['slug'] = 'required|unique:menu_option_items';
        }

        return $request->validate($rules);
    }
}
