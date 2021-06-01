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
            $table->string('price')->nullable()->change();
            $table->string('tax')->nullable()->change();
            $table->string('discount')->nullable()->change();
            $table->json('variants')->after('brand_id');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->string('price')->nullable()->change();
            $table->string('tax')->nullable()->change();
            $table->string('discount')->nullable()->change();
            $table->json('variants')->after('restaurant_category_id');
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
