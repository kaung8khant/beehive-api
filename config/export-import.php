<?php

return [
    'import' => [
        'customer' => 'CustomersImport',
        'shop' => 'ShopsImport',
        'shop-category' => 'ShopCategoriesImport',
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
    ],
    'export' => [
        'customer' => 'CustomersExport',
        'shop-customer' => 'ShopCustomersExport',
        'restaurant-customer' => 'RestaurantCustomersExport',
        'shop' => 'ShopsExport',
        'product' => 'ProductsExport',
        'shop-product' => 'ShopProductsExport',
        'shop-category' => 'ShopCategoriesExport',
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

        'restaurant-sales' => 'Sales\RestaurantSalesExport',
        'restaurant-vendor-sales' => 'Sales\RestaurantVendorSalesExport',
        'restaurant-branch-sales' => 'Sales\RestaurantBranchSalesExport',
        'restaurant-vendor-invoice' => 'Sales\RestaurantVendorInvoiceExport',
        'shop-invoice-sales' => 'Sales\ShopInvoiceSalesExport',
        'shop-sales' => 'Sales\ShopSalesExport',
        'shop-product-sales' => 'Sales\ProductSalesExport',
        'shop-vendor-product-sales' => 'Sales\ShopProductSalesExport',
        'shop-vendor-invoice' => 'Sales\ShopVendorInvoiceExport',
        'promocode-sales' => 'Sales\PromocodeSalesExport',
        'promocode-used-customers' => 'Sales\PromocodeUsedCustomerExport',
        'promocode-invoice' => 'Sales\PromocodeInvoiceSalesExport',

        'product-price-book' => 'ProductPriceBookExport',
    ],
    'name' => [
        'restaurant-vendor-sales',
        'restaurant-branch-sales',
        'restaurant-vendor-invoice',
    ],
];
