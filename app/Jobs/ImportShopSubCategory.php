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
use App\Models\ShopCategory;
use App\Models\ShopSubCategory;
use Illuminate\Database\QueryException;

class ImportShopSubCategory implements ShouldQueue, ShouldBeUnique
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
                'name' => ['required', 'unique:shop_sub_categories'],
                'shop_category_slug' => ['required','exists:App\Models\ShopCategory,slug'],
            ];

            $shopSubCategory=null;
            if (isset($row['id'])) {
                $shopSubCategory = ShopSubCategory::where('slug', $row['id'])->first();
                $rules['name'][1] = Rule::unique('shop_sub_categories')->ignore($shopSubCategory->id);
            }

            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $shopSubCategoryData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['name'],
                    'shop_category_id' =>ShopCategory::where('slug', $row['shop_category_slug'])->value('id'),
                ];

                if (!$shopSubCategory) {
                    try {
                        $shopSubCategory = ShopSubCategory::create($shopSubCategoryData);
                    } catch (QueryException $e) {
                        $shopSubCategory = ShopSubCategory::where('name', $row['name'])->first();
                        $shopSubCategoryData['slug'] = $shopSubCategory->slug;
                        $shopSubCategory->update($shopSubCategoryData);
                    }
                } else {
                    $shopSubCategoryData['slug'] = $shopSubCategory->slug;
                    $shopSubCategory->update($shopSubCategoryData);
                }
            }
        }
    }
}
