<?php

namespace Database\Factories;

use App\Helpers\StringHelper;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    use StringHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Announcement::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'slug' => $this->generateUniqueSlug(),
            'title' => $this->faker->text(30),
            'description' => $this->faker->paragraph(),
            'announcement_date' => $this->faker->date(),
        ];
    }
}
