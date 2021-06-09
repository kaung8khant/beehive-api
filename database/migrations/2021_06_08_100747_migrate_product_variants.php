<?php

use App\Helpers\StringHelper;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariation;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MigrateProductVariants extends Migration
{
    use StringHelper;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $products = DB::table('products')
            ->select('id', 'price', 'tax', 'discount')
            ->whereRaw('(select count(*) from product_variations where products.id = product_variations.product_id) < 2')
            ->get();

        $now = Carbon::now();

        foreach ($products as $product) {
            $variation = ProductVariation::where('product_id', $product->id)->first();

            $values = [];

            if ($variation) {
                foreach ($variation->productVariationValues as $variationValue) {
                    array_push($values, $variationValue->value);

                    $productVariant = [
                        'product_id' => $product->id,
                        'slug' => $this->generateUniqueSlug(),
                        'variant' => [
                            $variation->name => $variationValue->value,
                        ],
                        'price' => $product->price + $variationValue->price,
                        'tax' => $product->tax,
                        'discount' => $product->discount,
                        'is_enable' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    ProductVariant::create($productVariant);
                }

                $variants = [
                    'name' => $variation->name,
                    'values' => $values,
                ];

                Product::where('id', $product->id)->update(['variants' => $variants]);
            } else {
                $productVariant = [
                    'product_id' => $product->id,
                    'slug' => $this->generateUniqueSlug(),
                    'variant' => [
                        'name' => 'Standard',
                    ],
                    'price' => $product->price,
                    'tax' => $product->tax,
                    'discount' => $product->discount,
                    'is_enable' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                ProductVariant::create($productVariant);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
