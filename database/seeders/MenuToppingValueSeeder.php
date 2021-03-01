<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\MenuToppingValue;

class MenuToppingValueSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menuToppingValues = [
            [
                'value' => 'Baby Corn',
                'price' => 300,
                'slug' => $this->generateUniqueSlug(),
                'menu_topping_id' => 1,
            ],
            [
                'value' => 'Mushroom',
                'price' => 500,
                'slug' => $this->generateUniqueSlug(),
                'menu_topping_id' => 1,
            ],
            [
                'value' => 'Carrot',
                'price' => 300,
                'slug' => $this->generateUniqueSlug(),
                'menu_topping_id' => 1,
            ],
        ];

        foreach ($menuToppingValues as $menuToppingValue) {
            MenuToppingValue::create($menuToppingValue);
        }
    }
}
