<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    use StringHelper;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menus = [
            [
                "name" => "Chicken Curry",
                "name_mm" => "ကြက်သားဟင်း",
                "price"=>4500,
                "description"=>"Testing",
                "description_mm"=>"Testing",
                "slug" => $this->generateUniqueSlug(),
                "restaurant_id" => 1,
                "restaurant_category_id" => 1
            ],
            [
                "name" => "Pork Curry",
                "name_mm" => "ဝက်သားဟင်း",
                "price"=>4500,
                "description"=>"Testing",
                "description_mm"=>"Testing",
                "slug" => $this->generateUniqueSlug(),
                "restaurant_id" => 1,
                "restaurant_category_id" => 1
            ],
        ];
        foreach ($menus as $menu) {
            Menu::create($menu);
        }
    }
}
