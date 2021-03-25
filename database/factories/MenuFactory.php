<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuFactory extends Factory
{
    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Menu::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'slug' => $this->generateUniqueSlug(),
            'name' => $this->faker->text(30),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->numberBetween(1000, 10000),
            'tax' => $this->faker->numberBetween(1, 100),
            'restaurant_id' => Restaurant::pluck('id')->random(1)[0],
            'restaurant_category_id' => function (array $attributes) {
                return Restaurant::find($attributes['restaurant_id'])->availableCategories()->pluck('restaurant_category_id')->random(1)[0];
            },
        ];
    }
}
