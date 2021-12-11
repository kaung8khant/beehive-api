<?php

return [
    'import' => [
        'customer' => 'CustomersImport',
        'shop' => 'ShopsImport',
        'shop-category' => 'ShopCategoriesImport',
        'shop-main-category' => 'ShopMainCategoriesImport',
        'shop-sub-category' => 'ShopSubCategoriesImport',
        'shop-tag' => 'ShopTagsImport',
        'product' => 'ProductsImport',
        'brand' => 'BrandsImport',
        'shop-order' => 'ShopOrdersImport',
        'restaurant' => 'RestaurantsImport',
        'restaurant-branch' => 'RestaurantBranchesImport',
        'restaurant-category' => 'RestaurantCategoriesImport',
        'restaurant-tag' => 'RestaurantTagsImport',
        'restaurant-order' => 'RestaurantOrdersImport',
        'menu' => 'MenusImport',
        'customer-group' => 'CustomerGroupsImport',
        'product-price-book' => 'ProductPriceBookImport',
        'menu-price-book' => 'MenuPriceBookImport',
    ],
    'export' => [
        'customer' => 'CustomersExport',
        'shop-customer' => 'ShopCustomersExport',
        'restaurant-customer' => 'RestaurantCustomersExport',
        'shop' => 'ShopsExport',
        'product' => 'ProductsExport',
        'shop-product' => 'ShopProductsExport',
        'brand-product' => 'BrandProductsExport',
        'category-product' => 'CategoryProductsExport',
        'shop-category' => 'ShopCategoriesExport',
        'shop-main-category' => 'ShopMainCategoriesExport',
        'shop-sub-category' => 'ShopSubCategoriesExport',
        'shop-tag' => 'ShopTagsExport',
        'brand' => 'BrandsExport',
        'shop-order' => 'ShopOrdersExport',
        'restaurant' => 'RestaurantsExport',
        'restaurant-branch' => 'RestaurantBranchesExport',
        'restaurant-category' => 'RestaurantCategoriesExport',
        'restaurant-tag' => 'RestaurantTagsExport',
        'restaurant-order' => 'RestaurantOrdersExport',
        'menu' => 'MenusExport',
        'promocode' => 'PromocodesExport',
        'sms-log' => 'SmsLogsExport',
        'customer-group' => 'CustomerGroupsExport',
        'restaurant-branch-order' => 'RestaurantBranchOrdersExport',
        'vendor-shop-order' => 'VendorShopOrdersExport',
        'restaurant-branch-menu' => 'RestaurantBranchMenusExport',
        'restaurant-menu' => 'RestaurantMenuExport',
        'category-menu' => 'CategoryMenusExport',

        'restaurant-sales' => 'Sales\RestaurantSalesExport',
        'restaurant-vendor-sales' => 'Sales\RestaurantVendorSalesExport',
        'restaurant-branch-sales' => 'Sales\RestaurantBranchSalesExport',
        'restaurant-vendor-invoice' => 'Sales\RestaurantVendorInvoiceExport',
        'shop-invoice-sales' => 'Sales\ShopInvoiceSalesExport',
        'shop-sales' => 'Sales\ShopSalesExport',
        'shop-product-sales' => 'Sales\ProductSalesExport',
        'shop-category-sales' => 'Sales\ShopCategorySalesExport',
        'shop-vendor-product-sales' => 'Sales\ShopProductSalesExport',
        'shop-vendor-invoice' => 'Sales\ShopVendorInvoiceExport',
        'promocode-sales' => 'Sales\PromocodeSalesExport',
        'promocode-used-customers' => 'Sales\PromocodeUsedCustomerExport',
        'promocode-invoice' => 'Sales\PromocodeInvoiceSalesExport',

        'product-price-book' => 'ProductPriceBookExport',
        'menu-price-book' => 'MenuPriceBookExport',

        'customer-credit-sales' => 'Sales\CustomerCreditSalesExport',
    ],
    'name' => [
        'restaurant-vendor-sales',
        'restaurant-branch-sales',
        'restaurant-vendor-invoice',
    ],
];
