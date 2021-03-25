<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\MenuVariation;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuVariationFactory extends Factory
{
    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MenuVariation::class;

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
            'menu_id' => Menu::pluck('id')->random(1)[0],
        ];
    }
}
