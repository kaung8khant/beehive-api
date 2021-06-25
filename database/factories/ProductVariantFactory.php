<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'slug' => $this->generateUniqueSlug(),
            'product_id' => Product::pluck('id')->random(1)[0],
            'price' => $this->faker->numberBetween(1000, 10000),
            'vendor_price' => $this->faker->numberBetween(1000, 10000),
            'tax' => $this->faker->numberBetween(0, 50),
            'discount'=>$this->faker->numberBetween(0, 50),
            'variant' =>json_decode('[{"value":"Standard"}]'),
        ];
    }
}
