<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    use StringHelper;
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = City::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->city(),
            'name_mm' => $this->faker->unique()->city(),
            "slug" => $this->generateUniqueSlug(),
        ];
    }
}
