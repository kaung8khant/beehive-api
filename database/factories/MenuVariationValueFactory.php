<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\MenuVariationValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuVariationValueFactory extends Factory
{
    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MenuVariationValue::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'slug' => $this->generateUniqueSlug(),
            'value' => $this->faker->text(20),
            'price' => $this->faker->numberBetween(200, 3000),
        ];
    }
}
