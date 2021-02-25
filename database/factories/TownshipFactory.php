<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\Township;
use Illuminate\Database\Eloquent\Factories\Factory;

class TownshipFactory extends Factory
{
    use StringHelper;
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Township::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $mmFaker = app('Faker');
        return [
            'name' => $this->faker->unique()->state(),
            'name_mm' => $mmFaker->name(),
            "slug" => $this->generateUniqueSlug(),
        ];
    }
}
