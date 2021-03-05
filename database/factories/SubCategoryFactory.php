<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Helpers\StringHelper;
use App\Models\SubCategory;

class SubCategoryFactory extends Factory
{
    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubCategory::class;

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
            'name' => $this->faker->unique()->text(30),
            'name_mm' => $mmFaker->name(5),
        ];
    }
}
