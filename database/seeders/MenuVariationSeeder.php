<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\MenuVariation;

class MenuVariationSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menuVariations = [
            [
                'name' => 'Spicy Level',
                'slug' => $this->generateUniqueSlug(),
                'menu_id' => 1,
            ],
            [
                'name' => 'Bowl Size',
                'slug' => $this->generateUniqueSlug(),
                'menu_id' => 2,
            ],
        ];

        foreach ($menuVariations as $menuVariation) {
            MenuVariation::create($menuVariation);
        }
    }
}
