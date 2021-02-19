<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\MenuVariationValue;

class MenuVariationValueSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menuVariationValues = [
            [
                'name' => 'None Spicy',
                'value' => 1,
                'price' => 0,
                'slug' => $this->generateUniqueSlug(),
                'menu_variation_id' => 1,
            ],
            [
                'name' => 'Normal',
                'value' => 2,
                'price' => 0,
                'slug' => $this->generateUniqueSlug(),
                'menu_variation_id' => 1,
            ],
            [
                'name' => 'Too Spicy',
                'value' => 3,
                'price' => 0,
                'slug' => $this->generateUniqueSlug(),
                'menu_variation_id' => 1,
            ],
        ];

        foreach ($menuVariationValues as $menuVariationValue) {
            MenuVariationValue::create($menuVariationValue);
        }
    }
}
