<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\ShopCategory;
use App\Models\ShopSubCategory;
use Illuminate\Database\Seeder;

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
