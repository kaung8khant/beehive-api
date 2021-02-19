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
                'name' => 'Baby Corn',
                'value' => 1,
                'price' => 300,
                'slug' => $this->generateUniqueSlug(),
                'menu_topping_id' => 1,
            ],
            [
                'name' => 'Mushroom',
                'value' => 2,
                'price' => 500,
                'slug' => $this->generateUniqueSlug(),
                'menu_topping_id' => 1,
            ],
            [
                'name' => 'Carrot',
                'value' => 3,
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
