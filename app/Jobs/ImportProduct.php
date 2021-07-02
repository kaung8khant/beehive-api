<?php

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\ShopCategory;
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
                'name' => 'required|string',
                'description' => 'nullable|string',
                'price' => 'required|numeric|max:99999999',
                'vendor_price' => 'required|numeric|max:99999999',
                'tax' => 'required|numeric',
                'discount' => 'required|numeric',
                'is_enable' => 'required|boolean',
                'shop_slug' => 'required|exists:App\Models\Shop,slug',
                'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
                'shop_sub_category_slug' => 'nullable|exists:App\Models\ShopSubCategory,slug',
                'brand_slug' => 'nullable|exists:App\Models\Brand,slug',
            ];

            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $productVariant = null;

                if (isset($row['id'])) {
                    $productVariant = ProductVariant::where('slug', $row['product_variant_slug'])->first();
                }

                $productData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'tax' => $row['tax'],
                    'discount' => $row['discount'],
                    'is_enable' => $row['is_enable'],
                    'shop_id' => Shop::where('slug', $row['shop_slug'])->value('id'),
                    'shop_category_id' => ShopCategory::where('slug', $row['shop_category_slug'])->value('id'),
                    'shop_sub_category_id' => ShopSubCategory::where('slug', $row['shop_sub_category_slug'])->value('id'),
                    'brand_id' => Brand::where('slug', $row['brand_slug'])->value('id'),
                    'variants' => [],
                ];

                if (!$productVariant) {
                    $product = Product::create($productData);
                    $standardProductVariant= [
                        'product_id' => $product->id,
                        'slug' => StringHelper::generateUniqueSlug(),
                        'variant' => json_decode('[{"value":"Standard"}]'),
                        'price' => $row['price'],
                        'vendor_price' => $row['vendor_price'],
                        'tax' => $row['tax'],
                        'discount' => $row['discount'],
                    ];
                    ProductVariant::create($standardProductVariant);
                } else {
                    $productData['slug']=$productVariant->product->slug;
                    $productData['variants']=$productVariant->product->variants;
                    $productVariant->product->update($productData);

                    $productVariantData = [
                        'product_id' => $productVariant->product->id,
                        'slug' => $row['product_variant_slug'],
                        'price' => $row['price'],
                        'vendor_price' => $row['vendor_price'],
                        'tax' => $row['tax'],
                        'discount' => $row['discount'],
                    ];

                    $productVariant->update($productVariantData);
                }
            }
        }
    }
}
