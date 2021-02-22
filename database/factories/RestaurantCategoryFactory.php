<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\RestaurantCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class RestaurantCategoryFactory extends Factory
{
    use StringHelper;
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RestaurantCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'name_mm' => $this->faker->name(),
            "slug" => $this->generateUniqueSlug(),
        ];
    }
}
