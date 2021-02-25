<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

class RestaurantFactory extends Factory
{
    use StringHelper;
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Restaurant::class;

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
            'name_mm' => $mmFaker->name() . 'စားသောက်ဆိုင်',
            'is_official' => $this->faker->boolean(),
            'is_enable' => $this->faker->boolean(),
        ];
    }
}
