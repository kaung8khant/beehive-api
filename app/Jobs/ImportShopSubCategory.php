<?php

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\Product;
use App\Models\ShopCategory;
use App\Models\ShopSubCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
                'code' => ['required', 'size:2'],
                'name' => ['required', 'unique:shop_sub_categories'],
                'shop_category_code' => ['required', 'exists:App\Models\ShopCategory,code']];

            $shopSubCategory = null;

            $shopSubCategory = ShopSubCategory::where('name', $row['name'])->first();
            if ($shopSubCategory) {
                $rules['name'][1] = Rule::unique('shop_sub_categories')->ignore($shopSubCategory->id);
            }

            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $shopSubCategoryData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'shop_category_id' => $this->getShopCategoryIdByCode($row['shop_category_code']),
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
                    if ($this->checkProducts($shopSubCategory->id) && $shopSubCategory->code && $shopSubCategory->code !== $shopSubCategoryData['code']) {
                        return response()->json(['message' => 'Cannot update sub category code if there is a linked product.'], 403);
                    }
                    $shopSubCategory->update($shopSubCategoryData);
                    $this->updateProductCategoryIds($shopSubCategory->products, $shopSubCategoryData['shop_category_id']);
                }
            }
        }
    }

    public function checkProducts($id)
    {
        return Product::where('shop_sub_category_id', $id)->exists();
    }

    private function updateProductCategoryIds($products, $categoryId)
    {
        foreach ($products as $product) {
            $product->update([
                'shop_category_id' => $categoryId,
            ]);
        }
    }
    public function getShopCategoryIdByCode($code)
    {
        return ShopCategory::where('code', $code)->firstOrFail()->id;
    }
}
