<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\ShopSubCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopSubCategoryFactory extends Factory
{
    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ShopSubCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'slug' => $this->generateUniqueSlug(),
            'name' => $this->faker->unique()->text(30),
        ];
    }
}
