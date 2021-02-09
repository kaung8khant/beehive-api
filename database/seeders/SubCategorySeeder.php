<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    use StringHelper;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $subCategories = [
            [
                "name" => "Bath & Body",
                "name_mm" => "ရေချိုး & ကိုယ်ခန္ဓာ",
                "slug" => $this->generateUniqueSlug(),
                "shop_category_id"=>1,
            ],
            [
                "name" => "Skin Care",
                "name_mm" => "အရေပြားထိန်းသိမ်းမှု",
                "slug" => $this->generateUniqueSlug(),
                "shop_category_id"=>1,
            ],
            [
                "name" => "Clothing",
                "name_mm" => "အဝတ်အစား",
                "slug" => $this->generateUniqueSlug(),
                "shop_category_id"=>2,
            ],
            [
                "name" => "Women's Bags",
                "name_mm" => "အမျိုးသမီးအိတ်များ",
                "slug" => $this->generateUniqueSlug(),
                "shop_category_id"=>2,
            ],
        ];
        foreach ($subCategories as $subCategory) {
            SubCategory::create($subCategory);
        }
    }
}
