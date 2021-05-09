<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\Ads;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdsFactory extends Factory
{

    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Ads::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'slug' => $this->generateUniqueSlug(),
            'label' => $this->faker->text(20),
            'contact_person' => $this->faker->name(),
            'company_name' => $this->faker->company(),
            'phone_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'type' => 'banner',
            'source' => 'shop',
            'created_by' => 'admin',
        ];
    }
}
