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
        $this->call(RestaurantCategorySeeder::class);
        $this->call(RestaurantTagSeeder::class);
        $this->call(RestaurantSeeder::class);

        $this->call(UserSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(UserRoleSeeder::class);
        $this->call(CustomerSeeder::class);

        $this->call(RestaurantRestaurantCategorySeeder::class);
        $this->call(RestaurantRestaurantTagSeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(MenuVariationSeeder::class);
        $this->call(MenuToppingSeeder::class);
        $this->call(RestaurantBranchMenuSeeder::class);

        $this->call(ShopCategorySeeder::class);
        $this->call(ShopTagSeeder::class);
        $this->call(ShopSeeder::class);
        $this->call(ShopShopCategorySeeder::class);
        $this->call(ShopShopTagSeeder::class);

        $this->call(BrandSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(ProductVariationSeeder::class);
        $this->call(ProductVariationValueSeeder::class);

        $this->call(SettingSeeder::class);
        $this->call(FavoriteProductSeeder::class);
        $this->call(FavoriteRestaurantSeeder::class);
        $this->call(PromocodeSeeder::class);
        $this->call(PromocodeRuleSeeder::class);
        $this->call(PageSeeder::class);
    }
}
