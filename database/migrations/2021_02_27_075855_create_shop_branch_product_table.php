<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopBranchProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_branch_product', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_branch_id');
            $table->unsignedBigInteger('product_id');
            $table->primary(['shop_branch_id', 'product_id']);
            $table->foreign('shop_branch_id')->references('id')->on('shop_branches')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_branch_product');
    }
}
