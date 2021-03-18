<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\City;
use App\Models\Township;


class CitySeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $cities = [
        //     [
        //         'name' => 'Kachin',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Kaya',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Kayin',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Chin',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Mon',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Yakhine',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Eastern Shan',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Southern Shan',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Nothern Shan',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Sagaing',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Tanintharyi',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Nay Pyi Taw',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Pago(N)',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Pago',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Magway',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Mandalay',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Yangon',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        //     [
        //         'name' => 'Ayeyarwaddy',
        //         'slug' => $this->generateUniqueSlug(),
        //     ],
        // ];

        // foreach ($cities as $city) {
        //     City::create($city);
        // }
        City::factory()->count(3)->has(Township::factory()->count(3))->create();
        City::factory()->count(3)->has(Township::factory()->count(5))->create();
        City::factory()->count(10)->create();
    }
}
