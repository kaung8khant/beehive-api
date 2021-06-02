<?php

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\Township;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class ImportRestaurant implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uniqueKey;
    protected $rows;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uniqueKey, $rows)
    {
        ini_set('max_execution_time', 300);

        $this->uniqueKey = $uniqueKey;
        $this->rows = $rows;
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->uniqueKey;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->rows as $key => $row) {
            $restaurant = null;

            if (isset($row['id'])) {
                $restaurant = Restaurant::where('slug', $row['id'])->first();

                $rules = [
                    'name' => [
                        'required',
                        Rule::unique('restaurants')->ignore($restaurant->id),
                    ],
                    'is_enable' => 'required|boolean',
                ];

                $validator = Validator::make(
                    $row,
                    $rules
                );
            } else {
                if (isset($row['branch_contact_number'])) {
                    $row['branch_contact_number'] = str_replace([' ', '-'], '', $row['branch_contact_number']);
                }

                $rules = [
                    'name' => [
                        'required',
                        'unique:restaurants',
                    ],
                    'is_enable' => 'required|boolean',
                    'branch_name' => ['required'],
                    'branch_address' => ['nullable'],
                    'branch_contact_number' => ['required', 'phone:MM'],
                    'branch_opening_time' => ['required', 'date_format:H:i'],
                    'branch_closing_time' => ['required', 'date_format:H:i'],
                    'branch_latitude' => ['required', 'numeric'],
                    'branch_longitude' => ['required', 'numeric'],
                    'branch_township_slug' => ['nullable', 'exists:App\Models\Township,slug'],
                ];

                $validator = Validator::make(
                    $row,
                    $rules,
                    [
                        'branch_contact_number.phone' => 'Invalid phone number.',
                    ]
                );
            }

            if (!$validator->fails()) {
                $restaurantData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['name'],
                    'is_enable' => $row['is_enable'],
                ];

                if (!$restaurant) {
                    try {
                        $restaurant = restaurant::create($restaurantData);
                        RestaurantBranch::create([
                            'slug' => StringHelper::generateUniqueSlug(),
                            'name' => $row['branch_name'],
                            'contact_number' => PhoneNumber::make($row['branch_contact_number'], 'MM'),
                            'opening_time' => $row['branch_opening_time'],
                            'closing_time' => $row['branch_closing_time'],
                            'latitude' => $row['branch_latitude'],
                            'longitude' => $row['branch_longitude'],
                            'address' => $row['branch_address'],
                            'township_id' => Township::where('slug', $row['branch_township_slug'])->value('id'),
                            'restaurant_id' => $restaurant->id,
                        ]);
                    } catch (QueryException $e) {
                        $restaurant = Restaurant::where('name', $row['name'])->first();
                        $restaurantData['slug'] = $restaurant->slug;
                        $restaurant->update($restaurantData);
                    }
                } else {
                    $restaurantData['slug'] = $restaurant->slug;
                    $restaurant->update($restaurantData);
                }
            }
        }
    }
}
