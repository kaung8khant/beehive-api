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
                'value' => 'None Spicy',
                'price' => 500,
                'slug' => $this->generateUniqueSlug(),
                'menu_variation_id' => 1,
            ],
            [
                'value' => 'Normal',
                'price' => 1000,
                'slug' => $this->generateUniqueSlug(),
                'menu_variation_id' => 1,
            ],
            [
                'value' => 'Too Spicy',
                'price' => 1500,
                'slug' => $this->generateUniqueSlug(),
                'menu_variation_id' => 1,
            ],
        ];

        foreach ($menuVariationValues as $menuVariationValue) {
            MenuVariationValue::create($menuVariationValue);
        }
    }
}
