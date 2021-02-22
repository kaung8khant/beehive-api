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
        RestaurantCategory::factory()->count(120)->create();
    }
}
