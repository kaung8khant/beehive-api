<?php

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\Product;
use App\Models\ShopMainCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImportShopMainCategory implements ShouldQueue, ShouldBeUnique
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
                'code' => ['required','unique:shop_main_categories', 'size:2'],
                'name' => ['required', 'unique:shop_main_categories'],
            ];

            $shopMainCategory = null;

            $shopMainCategory = ShopMainCategory::where('name', $row['name'])->first();
            if ($shopMainCategory) {
                $rules['name'][1] = Rule::unique('shop_main_categories')->ignore($shopMainCategory->id);
                $rules['code'][1] = Rule::unique('shop_main_categories')->ignore($shopMainCategory->id);
            }

            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $shopMainCategoryData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'code' => $row['code'],
                    'name' => $row['name'],
                ];

                if (!$shopMainCategory) {
                    try {
                        $shopMainCategory = ShopMainCategory::create($shopMainCategoryData);
                    } catch (QueryException $e) {
                        $shopMainCategory = ShopMainCategory::where('name', $row['name'])->first();
                        $shopMainCategoryData['slug'] = $shopMainCategory->slug;
                        $shopMainCategory->update($shopMainCategoryData);
                    }
                } else {
                    $shopMainCategoryData['slug'] = $shopMainCategory->slug;
                    if ($this->checkProducts($shopMainCategory->id) && $shopMainCategory->code && $shopMainCategory->code !== $shopMainCategoryData['code']) {
                        return response()->json(['message' => 'Cannot update product type code if there is a linked product.'], 403);
                    }
                    $shopMainCategory->update($shopMainCategoryData);
                }
            }
        }
    }

    public function checkProducts($id)
    {
        $categoryIds = ShopMainCategory::where('id', $id)->firstOrFail()->shopCategories->pluck('id');
        return Product::whereIn('shop_category_id', $categoryIds)->exists();
    }
}
