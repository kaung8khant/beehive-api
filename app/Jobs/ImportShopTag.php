<?php

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\ShopTag;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImportShopTag implements ShouldQueue, ShouldBeUnique
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
                'name' => ['required', 'unique:shop_tags'],
            ];

            $shopTag = null;

            if (isset($row['id'])) {
                $shopTag = ShopTag::where('slug', $row['id'])->first();
                $rules['name'][1] = Rule::unique('shop_tags')->ignore($shopTag->id);
            }

            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $shopTagData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['name'],
                ];

                if (!$shopTag) {
                    try {
                        $shopTag = ShopTag::create($shopTagData);
                    } catch (QueryException $e) {
                        $shopTag = ShopTag::where('name', $row['name'])->first();
                        $shopTagData['slug'] = $shopTag->slug;
                        $shopTag->update($shopTagData);
                    }
                } else {
                    $shopTagData['slug'] = $shopTag->slug;
                    $shopTag->update($shopTagData);
                }
            }
        }
    }
}
