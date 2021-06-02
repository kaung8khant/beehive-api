<?php

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\CustomerGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImportCustomerGroup implements ShouldQueue, ShouldBeUnique
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
                'name' => ['required', 'string', 'max:200', 'unique:customer_groups'],
                'description' => ['nullable', 'string'],
            ];

            $customerGroup = null;

            if (isset($row['id'])) {
                $customerGroup = CustomerGroup::where('slug', $row['id'])->first();
                $rules['name'][3] = Rule::unique('customer_groups')->ignore($customerGroup->id);
            }

            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $customerGroupData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['name'],
                    'description' => $row['description'],
                ];

                if (!$customerGroup) {
                    try {
                        $customerGroup = CustomerGroup::create($customerGroupData);
                    } catch (QueryException $e) {
                        $customerGroup = CustomerGroup::where('name', $row['name'])->first();
                        $customerGroupData['slug'] = $customerGroup->slug;
                        $customerGroup->update($customerGroupData);
                    }
                } else {
                    $customerGroupData['slug'] = $customerGroup->slug;
                    $customerGroup->update($customerGroupData);
                }
            }
        }
    }
}
