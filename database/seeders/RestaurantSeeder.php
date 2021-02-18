<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\Restaurant;

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
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shwe Myanmar Restaurant',
                'name_mm' => 'ရွှေမြန်မာစားသောက်ဆိုင်',
                'is_official' => true,
                'is_enable' => true,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Danuphyu Daw Saw Yee Myanmar Restaurant',
                'name_mm' => 'ဓနုဖြူဒေါ်စောရီမြန်မာစားသောက်ဆိုင်',
                'is_official' => true,
                'is_enable' => true,
            ],
        ];

        foreach ($restaurants as $restaurant) {
            Restaurant::create($restaurant);
        }
    }
}
