<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuTopping;
use Illuminate\Http\Request;

class MenuToppingController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('menu_toppings', 'name', $request->by, $request->order);

        return MenuTopping::with('menu')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'menu_slug' => 'required|exists:App\Models\Menu,slug',
            'menu_toppings' => 'required|array',
            'menu_toppings.*.name' => 'required|string',
            'menu_toppings.*.price' => 'required|numeric',
            'menu_toppings.*.is_incremental' => 'required|boolean',
            'menu_toppings.*.max_quantity' => 'nullable|max:10',
            'menu_toppings.*.image_slug' => 'nullable|exists:App\Models\File,slug',
        ]);

        $menuId = $this->getMenuId($validatedData['menu_slug']);

        foreach ($validatedData['menu_toppings'] as $menuTopping) {
            $menuTopping['slug'] = $this->generateUniqueSlug();
            $menuTopping['menu_id'] = $menuId;

            MenuTopping::create($menuTopping);

            if (!empty($menuTopping['image_slug'])) {
                $this->updateFile($menuTopping['image_slug'], 'menu_toppings', $menuTopping['slug']);
            }
        }

        return response()->json(['message' => 'Successfully Created.'], 201);
    }

    public function show(MenuTopping $menuTopping)
    {
        return response()->json($menuTopping->load('menu'), 200);
    }

    public function update(Request $request, MenuTopping $menuTopping)
    {
        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['menu_id'] = $this->getMenuId($validatedData['menu_slug']);
        $menuTopping->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'menu_toppings', $menuTopping->slug);
        }

        return response()->json($menuTopping->load('menu'), 200);
    }

    public function destroy(MenuTopping $menuTopping)
    {
        foreach ($menuTopping->images as $image) {
            $this->deleteFile($image->slug);
        }

        $menuTopping->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function getToppingsByMenu(Request $request, Menu $menu)
    {
        $sorting = CollectionHelper::getSorting('menu_toppings', 'name', $request->by, $request->order);

        return MenuTopping::where('menu_id', $menu->id)
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'name' => 'required|string',
            'price' => 'required|numeric',
            'is_incremental' => 'required:boolean',
            'max_quantity' => 'nullable|max:10',
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
