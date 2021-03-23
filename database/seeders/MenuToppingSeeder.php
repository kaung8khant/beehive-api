<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\MenuTopping;

class MenuToppingSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menuToppings = [
            [
                'name' => 'Vegetables',
                'price' => 400,
                'slug' => $this->generateUniqueSlug(),
                'menu_id' => 1,
            ],
            [
                'name' => 'Meat',
                'price' => 1200,
                'slug' => $this->generateUniqueSlug(),
                'menu_id' => 2,
            ],
        ];

        foreach ($menuToppings as $menuTopping) {
            MenuTopping::create($menuTopping);
        }
    }
}
