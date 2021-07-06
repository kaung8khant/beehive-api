<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsLogFactory extends Factory
{
    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SmsLog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'batch_id' => $this->generateUniqueSlug(),
            'message_id' => $this->generateUniqueSlug(),
            'phone_number' => $this->faker->phoneNumber(),
            'message' => $this->faker->paragraph(),
            'message_parts' => rand(2, 5),
            'total_characters' => rand(200, 250),
            'encoding' => $this->faker->text(20),
            'type' => 'opt',
            'type' => $this->faker->randomElement(['opt', 'order']),
            'status' => $this->faker->randomElement(['Fail', 'Success', 'Error', 'Rejected']),
            'error_message' => $this->faker->text(20),
            'user_id' => User::pluck('id')->random(1)[0],
        ];
    }
}
