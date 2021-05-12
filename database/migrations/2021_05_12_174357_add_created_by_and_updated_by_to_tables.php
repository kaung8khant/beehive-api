<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByAndUpdatedByToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('customer_id');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('ads', function (Blueprint $table) {
            $table->string('created_by')->nullable()->change();
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('description');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('name');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('name');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('customer_groups', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('description');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('menu_toppings', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('menu_id');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('restaurant_category_id');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->renameColumn('modified_by', 'updated_by');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('brand_id');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('promocode_rules', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('promocode_id');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('promocodes', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('usage');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('restaurant_branches', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('township_id');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('restaurant_categories', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('name');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('promocode_id');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('restaurant_tags', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('name');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('is_enable');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('data_type');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('shop_categories', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('name');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('shop_orders', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('order_status');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('shop_sub_categories', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('shop_category_id');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('shop_tags', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('name');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('township_id');
            $table->string('updated_by')->nullable()->after('created_by');
        });

        Schema::table('townships', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('city_id');
            $table->string('updated_by')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['updated_by']);
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('ads', function (Blueprint $table) {
            $table->text('created_by')->nullable(false)->change();
            $table->dropColumn(['updated_by']);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('customer_groups', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('menu_toppings', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->renameColumn('updated_by', 'modified_by');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('promocode_rules', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('promocodes', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('restaurant_branches', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('restaurant_categories', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('restaurant_tags', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('shop_categories', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('shop_sub_categories', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('shop_tags', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('townships', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });
    }
}
