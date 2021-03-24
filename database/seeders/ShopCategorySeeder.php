<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\ShopCategory;
use App\Models\ShopSubCategory;

class ShopCategorySeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ShopCategory::factory()->count(30)->has(ShopSubCategory::factory()->count(3))->create();
    }
}
