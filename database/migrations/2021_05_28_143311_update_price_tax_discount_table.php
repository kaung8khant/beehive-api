<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePriceTaxDiscountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->change();
            $table->decimal('tax', 12, 2)->nullable()->change();
            $table->decimal('discount', 12, 2)->nullable()->change();
            $table->json('variants')->nullable()->after('brand_id');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->change();
            $table->decimal('tax', 12, 2)->nullable()->change();
            $table->decimal('discount', 12, 2)->nullable()->change();
            $table->json('variants')->nullable()->after('restaurant_category_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['variants']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['variants']);
        });
    }
}
