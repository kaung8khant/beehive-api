<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\RestaurantBranch;
use Illuminate\Database\Seeder;

class RestaurantBranchSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $restaurantBranches = [
            [
                'name' => 'Shwe Myanmar Restaurant Latha Branch',
                'name_mm' => 'ရွှေမြန်မာစားသောက်ဆိုင် လသာဆိုင်ခွဲ',
                'slug' => $this->generateUniqueSlug(),
                'address' => '320, 322 Shwe Bon Thar Rd, Yangon',
                'contact_number' => '0931860060',
                'opening_time' => '9:00',
                'closing_time' => '20:00',
                'latitude' => '16.77993329826223',
                'longitude' => '96.1555520998094',
                'is_enable' => true,
                'restaurant_id' => 1,
                'township_id' => 1,
            ],
            [
                'name' => 'Shwe Myanmar Restaurant Sule Branch',
                'name_mm' => 'ရွှေမြန်မာစားသောက်ဆိုင်  ဆူးလေဆိုင်ခွဲ',
                'slug' => $this->generateUniqueSlug(),
                'address' => '320, 322 Shwe Bon Thar Rd, Yangon',
                'contact_number' => '0931860061',
                'opening_time' => '9:00',
                'closing_time' => '20:00',
                'latitude' => '16.77993329826223',
                'longitude' => '96.1555520998094',
                'is_enable' => true,
                'restaurant_id' => 1,
                'township_id' => 2,
            ],
            [
                'name' => 'Danuphyu Daw Saw Yee Myanmar Restaurant Sule Branch',
                'name_mm' => 'ဓနုဖြူဒေါ်စောရီမြန်မာစားသောက်ဆိုင် ဆူး‌လေဆိုင်ခွဲ',
                'slug' => $this->generateUniqueSlug(),
                'address' => 'No. 175/177, 29th Street Pabedan Tsp, Yangon',
                'contact_number' => '01553685',
                'opening_time' => '9:00',
                'closing_time' => '20:00',
                'latitude' => '16.77993329826223',
                'longitude' => '96.1555520998094',
                'is_enable' => true,
                'restaurant_id' => 2,
                'township_id' => 2,
            ],
            [
                'name' => 'Danuphyu Daw Saw Yee Myanmar Restaurant Kyauktada Branch',
                'name_mm' => 'ဓနုဖြူဒေါ်စောရီမြန်မာစားသောက်ဆိုင် ကျောက်တံတားဆိုင်ခွဲ',
                'slug' => $this->generateUniqueSlug(),
                'address' => 'No. 175/177, 29th Street Pabedan Tsp, Yangon',
                'contact_number' => '01553688',
                'opening_time' => '9:00',
                'closing_time' => '20:00',
                'latitude' => '16.77993329826223',
                'longitude' => '96.1555520998094',
                'is_enable' => true,
                'restaurant_id' => 2,
                'township_id' => 3,
            ],
        ];

        foreach ($restaurantBranches as $restaurantBranch) {
            RestaurantBranch::create($restaurantBranch);
        }
    }
}
