<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

class FixProductVariantsColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $products = Product::pluck('variants', 'id');

        foreach ($products as $key => $variants) {
            if ($variants) {
                if (isset($variants['name'])) {
                    Product::where('id', $key)->update(['variants' => [$variants]]);
                }
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
