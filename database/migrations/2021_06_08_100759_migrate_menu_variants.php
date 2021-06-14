<?php

use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\MenuVariation;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MigrateMenuVariants extends Migration
{
    use StringHelper;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $menus = DB::table('menus')
            ->select('id', 'price', 'tax', 'discount')
            ->whereRaw('(select count(*) from menu_variations where menus.id = menu_variations.menu_id) < 2')
            ->get();

        $now = Carbon::now();

        foreach ($menus as $menu) {
            $variation = MenuVariation::where('menu_id', $menu->id)->first();

            $values = [];

            if ($variation) {
                foreach ($variation->menuVariationValues as $variationValue) {
                    array_push($values, $variationValue->value);

                    $menuVariant = [
                        'menu_id' => $menu->id,
                        'slug' => $this->generateUniqueSlug(),
                        'variant' => [
                            $variation->name => $variationValue->value,
                        ],
                        'price' => $menu->price + $variationValue->price,
                        'tax' => $menu->tax,
                        'discount' => $menu->discount,
                        'is_enable' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    MenuVariant::create($menuVariant);
                }

                $variants = [
                    'name' => $variation->name,
                    'values' => $values,
                ];

                Menu::where('id', $menu->id)->update(['variants' => $variants]);
            } else {
                $menuVariant = [
                    'menu_id' => $menu->id,
                    'slug' => $this->generateUniqueSlug(),
                    'variant' => [
                        'name' => 'Standard',
                    ],
                    'price' => $menu->price,
                    'tax' => $menu->tax,
                    'discount' => $menu->discount,
                    'is_enable' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                MenuVariant::create($menuVariant);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
