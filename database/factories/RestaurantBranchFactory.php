<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Helpers\StringHelper;
use App\Models\RestaurantBranch;

class RestaurantBranchFactory extends Factory
{
    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RestaurantBranch::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $mmFaker = app('Faker');

        return [
            'slug' => $this->generateUniqueSlug(),
            'name' => $this->faker->unique()->company() . ' Restaurant',
            'name_mm' => $mmFaker->name() . 'ဆိုင်ခွဲ',
            'is_enable' => $this->faker->boolean(),
            'address' => $this->faker->address(),
            'contact_number' => $this->faker->phoneNumber(),
            'opening_time' => rand(0, 11) . ':' . rand(0, 59),
            'closing_time' => rand(12, 23) . ':' . rand(0, 59),
            'latitude' => $this->faker->latitude(16.76, 16.93),
            'longitude' => $this->faker->longitude(96.17, 96.2),
            'township_id' => rand(1, 19),
        ];
    }
}
