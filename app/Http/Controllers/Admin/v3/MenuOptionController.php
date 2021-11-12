<?php

namespace App\Http\Controllers\Admin\v3;

use App\Events\DataChanged;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuOption;
use App\Models\MenuOptionItem;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MenuOptionController extends Controller
{
    use ResponseHelper, StringHelper;

    private $user;

    public function __construct()
    {
        if (Auth::guard('users')->check()) {
            $this->user = Auth::guard('users')->user();
        }
    }

    public function index(Menu $menu)
    {
        return MenuOption::exclude(['created_by', 'updated_by'])
            ->with(['options' => function ($query) {
                $query->exclude(['created_by', 'updated_by']);
            }])
            ->where('menu_id', $menu->id)
            ->get();
    }

    public function store(Request $request, Menu $menu)
    {
        $validatedData = $this->validateCreateMenuOption($request);

        try {
            DB::transaction(function () use ($request, $menu, $validatedData) {
                $this->createMenuOptions($request, $menu->id, $validatedData);
            });

            $menu->refresh()->load([
                'menuOptions' => function ($query) {
                    $query->exclude(['created_by', 'updated_by']);
                },
                'menuOptions.options' => function ($query) {
                    $query->exclude(['created_by', 'updated_by']);
                },
            ]);

            return response()->json($menu, 201);
        } catch (QueryException $e) {
            return $this->generateResponse('There is already same option name for this menu.', 409, true);
        }
    }

    public function show(Menu $menu, MenuOption $option)
    {
        return $option->makeHidden(['created_by', 'updated_by'])
            ->load(['options' => function ($query) {
                $query->exclude(['created_by', 'updated_by']);
            }]);
    }

    public function update(Request $request, Menu $menu, MenuOption $option)
    {
        $validatedData = $this->validateUpdateMenuOption($request);

        try {
            $validatedData['menu_id'] = $menu->id;
            $option->update($validatedData);

            return $option->makeHidden(['created_by', 'updated_by'])
                ->load(['options' => function ($query) {
                    $query->exclude(['created_by', 'updated_by']);
                }]);
        } catch (QueryException $e) {
            return $this->generateResponse('There is already same option name for this menu.', 409, true);
        }
    }

    public function destroy(Menu $menu, MenuOption $option)
    {
        $option->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function createMenuOptions($request, $menuId, $options)
    {
        foreach ($options as $option) {
            $option['slug'] = $this->generateUniqueSlug();
            $option['menu_id'] = $menuId;

            $menuOption = MenuOption::create($option);
            DataChanged::dispatch($this->user, 'create', 'menu_options', $option['slug'], $request->url(), 'success', $option);

            foreach ($option['options'] as $item) {
                $item['menu_option_id'] = $menuOption->id;
                $item['slug'] = $this->generateUniqueSlug();
                MenuOptionItem::create($item);

                DataChanged::dispatch($this->user, 'create', 'menu_option_items', $item['slug'], $request->url(), 'success', $item);
            }
        }
    }

    private function validateCreateMenuOption($request)
    {
        return $request->validate([
            '*' => 'array',
            '*.name' => 'required|string',
            '*.max_choice' => 'required',
            '*.options' => 'required|array',
            '*.options.*.name' => 'required|string',
            '*.options.*.price' => 'required|numeric',
        ]);
    }

    private function validateUpdateMenuOption($request)
    {
        return $request->validate([
            'name' => 'required|string',
            'max_choice' => 'required|integer',
        ]);
    }
}
