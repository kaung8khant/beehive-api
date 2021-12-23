<?php

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\ShopMainCategory;
use App\Models\ShopSubCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;

class ImportProduct implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uniqueKey;
    protected $rows;
    protected $userId;

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
    public function __construct($uniqueKey, $rows, $userId)
    {
        ini_set('max_execution_time', 300);

        $this->uniqueKey = $uniqueKey;
        $this->rows = $rows;
        $this->userId = $userId;
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
        foreach ($this->rows as $row) {
            $rules = [
                'name' => 'required|string',
                'description' => 'nullable|string',
                'price' => 'required|numeric|max:99999999',
                'vendor_price' => 'required|numeric|max:99999999',
                'tax' => 'required|numeric',
                'discount' => 'required|numeric',
                'is_enable' => 'required|boolean',
                'shop_slug' => 'required|exists:App\Models\Shop,slug',
                'product_type_code' => 'required|exists:App\Models\ShopMainCategory,code',
                'shop_category_code' => 'required|exists:App\Models\ShopCategory,code',
                'shop_sub_category_code' => 'nullable|exists:App\Models\ShopSubCategory,code',
                'brand_code' => 'nullable|exists:App\Models\Brand,code',
            ];

            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $productVariant = null;

                if (isset($row['code'])) {
                    $productVariant = ProductVariant::where('slug', $row['product_variant_slug'])->first();
                }

                $shopMainCategoryId = ShopMainCategory::where('code', $row['product_type_code'])->value('id');
                $shopCategoryId = ShopCategory::where('code', $row['shop_category_code'])->where('shop_main_category_id', $shopMainCategoryId)->value('id');
                $shopSubCategoryId = ShopSubCategory::where('code', $row['shop_sub_category_code'])->where('shop_category_id', $shopCategoryId)->value('id');

                $productData = [
                    'slug' => StringHelper::generateUniqueSlugWithTable('products'),
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'tax' => $row['tax'],
                    'discount' => $row['discount'],
                    'is_enable' => $row['is_enable'],
                    'shop_id' => Shop::where('slug', $row['shop_slug'])->value('id'),
                    'shop_category_id' => $shopCategoryId,
                    'shop_sub_category_id' => $shopSubCategoryId,
                    'brand_id' => Brand::where('code', $row['brand_code'])->value('id'),
                    'variants' => [],
                    'created_by' => $this->userId,
                    'updated_by' => $this->userId,
                ];

                if (!$productVariant) {
                    $product = Product::create($productData);

                    ProductVariant::create([
                        'slug' => StringHelper::generateUniqueSlugWithTable('product_variants'),
                        'product_id' => $product->id,
                        'code' => '00',
                        'variant' => json_decode('[{"name":"default","value":"Standard"}]'),
                        'price' => $row['price'],
                        'vendor_price' => $row['vendor_price'],
                        'tax' => $row['tax'],
                        'discount' => $row['discount'],
                    ]);
                } else {
                    $productData['slug'] = $productVariant->product->slug;
                    $productData['variants'] = $productVariant->product->variants;
                    $productVariant->product->update($productData);

                    $productVariant->update([
                        'slug' => $row['product_variant_slug'],
                        'product_id' => $productVariant->product->id,
                        'price' => $row['price'],
                        'vendor_price' => $row['vendor_price'],
                        'tax' => $row['tax'],
                        'is_enable' => isset($row['variant_is_enable']) ? $row['variant_is_enable'] : '1',
                        'discount' => $row['discount'],
                    ]);
                }
            }
        }
    }
}
