<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(UserRoleSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(CitySeeder::class);
        $this->call(RestaurantTagSeeder::class);
        $this->call(ShopTagSeeder::class);
        $this->call(RestaurantCategorySeeder::class);
        $this->call(ShopCategorySeeder::class);
        $this->call(SubCategorySeeder::class);
        $this->call(RestaurantSeeder::class);
        $this->call(ShopSeeder::class);
        $this->call(TagRestaurantSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(ProductVariationSeeder::class);
        $this->call(ProductVariationValueSeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(MenuVariationSeeder::class);
        $this->call(MenuToppingSeeder::class);
        $this->call(MenuVariationValueSeeder::class);
        $this->call(MenuToppingValueSeeder::class);
        $this->call(TagShopSeeder::class);
        $this->call(CategoryRestaurantSeeder::class);
        $this->call(CategoryShopSeeder::class);
        $this->call(ShopBranchSeeder::class);
        $this->call(SettingSeeder::class);
        // $this->call(OrderSeeder::class);
        // $this->call(OrderContactSeeder::class);
        // $this->call(OrderStatusSeeder::class);
        // $this->call(OrderItemSeeder::class);
        $this->call(FavoriteShopSeeder::class);
        $this->call(FavoriteRestaurantSeeder::class);
        // $this->call(RatingSeeder::class);
    }
}
