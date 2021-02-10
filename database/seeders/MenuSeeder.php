<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
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
            ],
            [
                "name" => "Pork Curry",
                "name_mm" => "ဝက်သားဟင်း",
                "price"=>4500,
                "description"=>"Testing",
                "description_mm"=>"Testing",
                "slug" => $this->generateUniqueSlug(),
            ],
        ];
        foreach ($menus as $menu) {
            Menu::create($menu);
        }
    }
}
