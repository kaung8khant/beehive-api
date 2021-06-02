<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\Township;
use Illuminate\Database\QueryException;
use Propaganistas\LaravelPhone\PhoneNumber;

class ImportRestaurantBranch implements ShouldQueue, ShouldBeUnique
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
            if (isset($row['contact_number'])) {
                $row['contact_number'] = str_replace([' ', '-'], '', $row['contact_number']);
            }
            $rules = [
                'name' => ['required', 'unique:restaurant_branches'],
                'is_enable' => ['required','boolean'],
                'address' => ['nullable'],
                'contact_number' => ['required','phone:MM'],
                'opening_time' => ['required','date_format:H:i'],
                'closing_time' => ['required','date_format:H:i'],
                'latitude' => ['required','numeric'],
                'longitude' => ['required','numeric'],
                'township_slug' => ['nullable','exists:App\Models\Township,slug'],
                'restaurant_slug' => ['required','exists:App\Models\Restaurant,slug'],
            ];

            $restaurantBranch=null;
            if (isset($row['id'])) {
                $restaurantBranch = RestaurantBranch::where('slug', $row['id'])->first();
                $rules['name'][1] = Rule::unique('restaurant_branches')->ignore($restaurantBranch->id);
            }

            $validator = Validator::make(
                $row,
                $rules,
                [
                    'contact_number.phone' => 'Invalid phone number.',
                ]
            );

            if (!$validator->fails()) {
                $restaurantBranchData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['name'],
                    'contact_number' => PhoneNumber::make($row['contact_number'], 'MM'),
                    'opening_time' => $row['opening_time'],
                    'closing_time' => $row['closing_time'],
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'address' => $row['address'],
                    'is_enable' => $row['is_enable'],
                    'township_id' => Township::where('slug', $row['township_slug'])->value('id'),
                    'restaurant_id' => Restaurant::where('slug', $row['restaurant_slug'])->value('id'),
                ];

                if (!$restaurantBranch) {
                    try {
                        $restaurantBranch = RestaurantBranch::create($restaurantBranchData);
                    } catch (QueryException $e) {
                        $restaurantBranch = RestaurantBranch::where('name', $row['name'])->first();
                        $restaurantBranchData['slug'] = $restaurantBranch->slug;
                        $restaurantBranch->update($restaurantBranchData);
                    }
                } else {
                    $restaurantBranchData['slug'] = $restaurantBranch->slug;
                    $restaurantBranch->update($restaurantBranchData);
                }
            }
        }
    }
}
