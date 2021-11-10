<?php

namespace App\Http\Controllers\Admin;

use App\Events\DataChanged;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuVariantController extends Controller
{
    use FileHelper, StringHelper;

    private $user;

    public function __construct()
    {
        if (Auth::guard('users')->check()) {
            $this->user = Auth::guard('users')->user();
        }
    }

    public function updateVariants(Request $request, Menu $menu)
    {
        $validatedData = $request->validate([
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string',
            'variants.*.values' => 'required|array',

            'menu_variants' => 'required',
            'menu_variants.*.slug' => 'nullable|exists:App\Models\MenuVariant,slug',
            'menu_variants.*.variant' => 'required',
            'menu_variants.*.price' => 'required|numeric',
            'menu_variants.*.tax' => 'required|numeric',
            'menu_variants.*.discount' => 'required|numeric',
            'menu_variants.*.is_enable' => 'required|boolean',
            'menu_variants.*.image_slug' => 'nullable|exists:App\Models\File,slug',
        ]);

        if (isset($validatedData['variants'])) {
            $menu->update([
                'variants' => $validatedData['variants'],
            ]);

            DataChanged::dispatch($this->user, 'update', 'menus', $menu->slug, $request->url(), 'success', $validatedData['variants']);
        }

        $variantSlugs = $menu->menuVariants->pluck('slug');

        foreach ($validatedData['menu_variants'] as $data) {
            if (isset($data['slug']) && $variantSlugs->contains($data['slug'])) {
                $menuVariant = MenuVariant::where('slug', $data['slug'])->first();
                $menuVariant->update($data);

                $arrKey = $variantSlugs->search($data['slug']);
                unset($variantSlugs[$arrKey]);

                DataChanged::dispatch($this->user, 'update', 'menu_variants', $data['slug'], $request->url(), 'success', $data);
            } else {
                $data['menu_id'] = $menu->id;
                $data['slug'] = $this->generateUniqueSlug();

                MenuVariant::create($data);
                DataChanged::dispatch($this->user, 'create', 'menu_variants', $data['slug'], $request->url(), 'success', $data);
            }

            if (isset($data['image_slug'])) {
                $this->updateFile($data['image_slug'], 'menu_variants', $data['slug']);
            }
        }

        foreach ($variantSlugs as $slug) {
            $menuVariant = MenuVariant::where('slug', $slug)->first();
            $menuVariant->delete();
        }

        return response()->json($menu->refresh()->load('menuVariants'), 200);
    }

    public function toggleEnable(Request $request, MenuVariant $menuVariant)
    {
        $menuVariant->update(['is_enable' => !$menuVariant->is_enable]);

        $status = $menuVariant->is_enable ? 'enable' : 'disable';
        DataChanged::dispatch($this->user, $status, 'menu_variants', $menuVariant->slug, $request->url(), 'success');

        return response()->json(['message' => 'Success.'], 200);
    }

    public function updateVariantPrice(Request $request, MenuVariant $menuVariant)
    {
        $validatedData = $request->validate([
            'price' => 'required|numeric',
            'discount' => 'required|numeric',
            'tax' => 'required|numeric',
        ]);

        $menuVariant->update($validatedData);

        $menu = Menu::with('menuVariants')
            ->where('id', $menuVariant->menu_id)
            ->first();

        return response()->json($menu, 200);
    }
}
