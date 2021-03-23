<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Helpers\StringHelper;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopSubCategory;
use App\Models\Brand;

class ProductFactory extends Factory
{
    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

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
            'name' => $this->faker->text(30),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->numberBetween(1000, 10000),
            'tax' => $this->faker->numberBetween(0, 100),
            'shop_id' => Shop::pluck('id')->random(1)[0],
            'shop_category_id' => function (array $attributes) {
                return  Shop::find($attributes['shop_id'])->availableCategories()->pluck('shop_category_id')->random(1)[0];
            },
            'shop_sub_category_id' => function (array $attributes) {
                return  ShopSubCategory::where('shop_category_id', $attributes['shop_category_id'])->pluck('id')->random(1)[0];
            },
            'brand_id' => function () {
                $condition = rand(0, 1);
                return $condition ? Brand::pluck('id')->random(1)[0] : null;
            },
        ];
    }
}
