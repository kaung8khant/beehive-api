<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    use StringHelper;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $restaurants = [
            [
                "name" => "Shwe Myanmar Restaurant",
                "name_mm" => "ရွှေမြန်မာစားသောက်ဆိုင်",
                "official" => true ,
                "enable" => true ,
                "slug" => $this->generateUniqueSlug(),
            ],
            [
                "name" => "Danuphyu Daw Saw Yee Myanmar Restaurant",
                "name_mm" => "ဓနုဖြူဒေါ်စောရီမြန်မာစားသောက်ဆိုင်",
                "official" => true ,
                "enable" => true ,
                "slug" => $this->generateUniqueSlug(),
            ],
        ];
        foreach ($restaurants as $restaurant) {
            Restaurant::create($restaurant);
        }
    }
}
