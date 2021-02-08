<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\StoreCategory;
use Illuminate\Database\Seeder;

class StoreCategorySeeder extends Seeder
{
    use StringHelper;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $storeCategories = [
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
        foreach ($storeCategories as $storeCategory) {
            StoreCategory::create($storeCategory);
        }
    }
}
