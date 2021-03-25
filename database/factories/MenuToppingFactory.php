<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Helpers\StringHelper;
use App\Models\MenuTopping;
use App\Models\Menu;

class MenuToppingFactory extends Factory
{
    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MenuTopping::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'slug' => $this->generateUniqueSlug(),
            'name' => $this->faker->text(20),
            'price' => $this->faker->numberBetween(200, 3000),
            'is_incremental' => $this->faker->boolean(),
            'max_quantity' => function (array $attributes) {
                return $attributes['is_incremental'] ? rand(2, 5) : null;
            },
            'menu_id' => Menu::pluck('id')->random(1)[0],
        ];
    }
}
