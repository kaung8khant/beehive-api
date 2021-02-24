<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\RestaurantBranch;
use App\Models\Township;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'name' => $this->faker->company() . ' Restaurant',
            'name_mm' => $mmFaker->name() . 'ဆိုင်ခွဲ',
            'is_enable' => $this->faker->boolean(),
            'address' => $this->faker->address(),
            'contact_number' => $this->faker->phoneNumber(),
            'opening_time' => rand(0, 11) . ':' . rand(0, 59),
            'closing_time' => rand(12, 23) . ':' . rand(0, 59),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'township_id' => rand(1, 19),
        ];
    }
}
