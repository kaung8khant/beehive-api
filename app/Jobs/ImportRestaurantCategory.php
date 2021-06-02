<?php

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\RestaurantCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImportRestaurantCategory implements ShouldQueue, ShouldBeUnique
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
            $rules = [
                'name' => ['required', 'unique:restaurant_categories'],
            ];

            $restaurantCategory = null;

            if (isset($row['id'])) {
                $restaurantCategory = RestaurantCategory::where('slug', $row['id'])->first();
                $rules['name'][1] = Rule::unique('restaurant_categories')->ignore($row['id']);
            }

            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $restaurantCategoryData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['name'],
                ];

                if (!$restaurantCategory) {
                    try {
                        $restaurantCategory = RestaurantCategory::create($restaurantCategoryData);
                    } catch (QueryException $e) {
                        $restaurantCategory = RestaurantCategory::where('name', $row['name'])->first();
                        $restaurantCategoryData['slug'] = $restaurantCategory->slug;
                        $restaurantCategory->update($restaurantCategoryData);
                    }
                } else {
                    $restaurantCategoryData['slug'] = $restaurantCategory->slug;
                    $restaurantCategory->update($restaurantCategoryData);
                }
            }
        }
    }
}
