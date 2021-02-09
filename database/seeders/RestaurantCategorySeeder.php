<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\RestaurantCategory;
use Illuminate\Database\Seeder;

class RestaurantCategorySeeder extends Seeder
{
    use StringHelper;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $restaurantCategories = [
            [
                "name" => "Fast food",
                "name_mm" => "အမြန်ပြင်အစားအစာ",
                "slug" => $this->generateUniqueSlug(),
            ],
            [
                "name" => "Fine dining",
                "name_mm" => "ကောင်းမွန်သောထမင်းစားခန်း",
                "slug" => $this->generateUniqueSlug(),
            ],
        ];
        foreach ($restaurantCategories as $restaurantCategory) {
            RestaurantCategory::create($restaurantCategory);
        }
    }
}
