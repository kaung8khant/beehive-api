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
use App\Models\RestaurantTag;

class ImportRestaurantTag implements ShouldQueue, ShouldBeUnique
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
                'name' => ['required', 'unique:restaurant_tags'],
            ];

            $restaruantTag=null;

            if (isset($row['id'])) {
                $restaruantTag = RestaurantTag::where('slug', $row['id'])->first();
                $rules['name'][1] = Rule::unique('restaurant_tags')->ignore($restaruantTag->id);
            }

            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $restaruantTagData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['name'],
                ];

                if (!$restaruantTag) {
                    $restaruantTag = RestaurantTag::create($restaruantTagData);
                } else {
                    $restaruantTagData['slug'] = $restaruantTag->slug;
                    $restaruantTag->update($restaruantTagData);
                }
            }
        }
    }
}
