<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\ShopCategory;
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
        $shopCategories = [
            [
                "name" => "Health & Beauty",
                "name_mm" => "ကျန်းမာရေးနှင့်အလှအပ",
                "slug" => $this->generateUniqueSlug(),
            ],
            [
                "name" => "Women's Fashion",
                "name_mm" => "အမျိုးသမီးဖက်ရှင်",
                "slug" => $this->generateUniqueSlug(),
            ],
        ];
        foreach ($shopCategories as $shopCategory) {
            ShopCategory::create($shopCategory);
        }
    }
}
