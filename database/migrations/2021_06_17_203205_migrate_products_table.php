<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

class MigrateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $products = Product::pluck('variants', 'id');

        foreach ($products as $key => $product) {
            if ($product) {
                $values = [];
                foreach ($product['values'] as $value) {
                    $values[] = [
                        'value' => $value,
                    ];
                }

                $data = [
                    'name' => $product['name'],
                    'values' => $values,
                    'ui' => 'button',
                ];

                Product::where('id', $key)->update(['variants' => $data]);
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
