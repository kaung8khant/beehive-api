<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Helpers\StringHelper;
use App\Models\Shop;
use App\Models\Township;

class ShopFactory extends Factory
{
    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Shop::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'slug' => $this->generateUniqueSlug(),
            'name' => $this->faker->unique()->company(),
            'is_official' => $this->faker->boolean(),
            'address' => $this->faker->address(),
            'contact_number' => $this->faker->phoneNumber(),
            'opening_time' => rand(0, 11) . ':' . rand(0, 59),
            'closing_time' => rand(12, 23) . ':' . rand(0, 59),
            'latitude' => $this->faker->latitude(16.76, 16.93),
            'longitude' => $this->faker->longitude(96.17, 96.2),
            'township_id' => function () {
                return  Township::pluck('id')->random(1)[0];
            },
        ];
    }
}
