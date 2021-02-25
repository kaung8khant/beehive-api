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
        $mmFaker = app('Faker');
        return [
            'name' => $this->faker->text(30),
            'name_mm' => $mmFaker->name(5),
            "slug" => $this->generateUniqueSlug(),
        ];
    }
}
