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
                'name_mm' => 'Vegetables (MM)',
                'price' => 400.00,
                'slug' => $this->generateUniqueSlug(),
                'menu_id' => 1,
            ],
            [
                'name' => 'Meat',
                'name_mm' => 'Meat (MM)',
                'price' => 1200.00,
                'slug' => $this->generateUniqueSlug(),
                'menu_id' => 2,
            ],
        ];

        foreach ($menuToppings as $menuTopping) {
            MenuTopping::create($menuTopping);
        }
    }
}
