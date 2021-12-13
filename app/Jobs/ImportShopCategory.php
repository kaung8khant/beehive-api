<?php

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\Product;
use App\Models\ShopCategory;
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
use App\Helpers\ResponseHelper;

class ImportShopCategory implements ShouldQueue, ShouldBeUnique
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
                'code' => ['required', 'size:3'],
                'name' => ['required', 'unique:shop_categories'],
                'product_type_code' => ['nullable', 'exists:App\Models\ShopMainCategory,code']
            ];

            $shopCategory = null;

            $shopCategory = ShopCategory::where('name', $row['name'])->first();
            if ($shopCategory) {
                $rules['name'][1] = Rule::unique('shop_categories')->ignore($shopCategory->id);
            }


            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $shopCategoryData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'code' => $row['code'],
                    'name' => $row['name'],
                ];
                if ($this->getMainCategoryByCode($row['product_type_code'])) {
                    $shopCategoryData['shop_main_category_id'] =$this->getMainCategoryByCode($row['product_type_code'])->id;
                }

                if (!$shopCategory) {
                    try {
                        $shopCategory = ShopCategory::create($shopCategoryData);
                    } catch (QueryException $e) {
                        $shopCategory = ShopCategory::where('name', $row['name'])->first();
                        $shopCategoryData['slug'] = $shopCategory->slug;
                        $shopCategory->update($shopCategoryData);
                    }
                } else {
                    try {
                        $shopCategoryData['slug'] = $shopCategory->slug;
                        if ($this->checkProducts($shopCategory->id) && $shopCategory->code && $shopCategory->code !== $shopCategoryData['code']) {
                            return response()->json(['message' => 'Cannot update category code if there is a linked product.'], 403);
                        }
                        $shopCategory->update($shopCategoryData);
                    } catch (QueryException $e) {
                        if (strpos($e->getMessage(), 'shop_categories_shop_main_category_id_code_unique') !== false) {
                            return ResponseHelper::generateValidateError('code', 'The code has already been taken for this product type.');
                        }

                        if (strpos($e->getMessage(), 'shop_categories_shop_main_category_id_name_unique') !== false) {
                            return ResponseHelper::generateValidateError('name', 'The name has already been taken for this product type.');
                        }
                    }
                }
            }
        }
    }

    public function checkProducts($id)
    {
        return Product::where('shop_category_id', $id)->exists();
    }

    public function getMainCategoryByCode($code)
    {
        return ShopMainCategory::where('code', $code)->first();
    }
}
