<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\MenuVariation;
use App\Models\MenuVariationValue;

class SlugFixController
{
    public function fix($table)
    {
        $model = '\App\Models\\' . $table;
        $data = $model::all();

        foreach ($data as $key) {
            if (is_numeric(substr($key->slug, 0, 6)) && substr($key->slug, 6, 1) == 'E') {
                do {
                    $randomString = strtoupper(substr(str_shuffle(MD5(microtime())), 0, 8));
                } while (is_numeric(substr($randomString, 0, 6)) && substr($randomString, 6, 1) == 'E');

                $key->slug = $randomString;
                $key->save();
            }
        }

        return ['status' => 'success'];
    }

    public function test()
    {
        $menus = Menu::with(['menuVariants' => function ($query) {
            $query->orderBy('price', 'asc');
        }])
            ->orderBy('id', 'asc')
            ->get();

        foreach ($menus as $menu) {
            $menuVariants = $menu->menuVariants->filter(function ($variant) {
                return count($variant->variant) === 1;
            });

            if (count($menuVariants) > 0) {
                try {
                    $menu->update([
                        'price' => $menuVariants[0]->price,
                        'tax' => $menuVariants[0]->tax,
                        'discount' => $menuVariants[0]->discount,
                    ]);
                } catch (\Exception $e) {
                    return $menu;
                }

                if ($menuVariants[0]->variant[0]['name'] !== 'default') {
                    MenuVariation::where('menu_id', $menu->id)->delete();

                    $menuVariation = MenuVariation::create([
                        'slug' => StringHelper::generateUniqueSlug(),
                        'name' => $menuVariants[0]->variant[0]['name'],
                        'menu_id' => $menu->id,
                    ]);

                    foreach ($menuVariants as $variant) {
                        MenuVariationValue::create([
                            'slug' => StringHelper::generateUniqueSlug(),
                            'value' => $variant->variant[0]['value'],
                            'price' => $variant->price - $menu->price,
                            'menu_variation_id' => $menuVariation->id,
                        ]);
                    }
                }
            }
        }
    }
}
