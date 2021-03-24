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
                'is_incremental' => 1,
                'max_quantity' => 5,
                'menu_id' => 1,
            ],
            [
                'name' => 'Noodle',
                'price' => 500,
                'slug' => $this->generateUniqueSlug(),
                'is_incremental' => 1,
                'max_quantity' => 5,
                'menu_id' => 1,
            ],
            [
                'name' => 'Meat',
                'price' => 1500,
                'slug' => $this->generateUniqueSlug(),
                'is_incremental' => 1,
                'max_quantity' => 5,
                'menu_id' => 2,
            ],
            [
                'name' => 'Boba Jelly',
                'price' => 800,
                'slug' => $this->generateUniqueSlug(),
                'is_incremental' => 0,
                'menu_id' => 3,
            ],
        ];

        foreach ($menuToppings as $menuTopping) {
            MenuTopping::create($menuTopping);
        }
    }
}
