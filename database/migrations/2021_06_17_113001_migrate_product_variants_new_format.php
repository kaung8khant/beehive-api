<?php

use App\Models\ProductVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateProductVariantsNewFormat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $productVariants = ProductVariant::pluck('variant', 'id');

        foreach ($productVariants as $key => $variant) {
            if (count($variant) === 1) {
                $_key = array_keys($variant)[0];
                $_value = $variant[$_key];

                $data = [
                    'name' => $_key == 'name' ? 'default' : $_key,
                    'value' => $_value,
                ];

                ProductVariant::where('id', $key)->update(['variant' => [$data]]);
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
