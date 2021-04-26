<?php

namespace App\Helpers;

use App\Helpers\PromocodeHelper;
use App\Helpers\StringHelper;
use App\Models\Product;
use App\Models\ProductVariationValue;
use App\Models\ShopOrder;
use App\Models\ShopOrderContact;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderStatus;
use App\Models\ShopOrderVendor;
use App\Models\Township;

trait ShopOrderHelper
{
    public static function validateOrder($request, $customerSlug = false)
    {
        $rules = [
            'slug' => 'required',
            'order_date' => 'required|date_format:Y-m-d',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:pickup,delivery',
            'promo_code_slug' => 'nullable|string|exists:App\Models\Promocode,slug',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'address' => 'required',
            'address.house_number' => 'required|string',
            'address.floor' => 'nullable|string',
            'address.street_name' => 'required|string',
            'address.latitude' => 'nullable|numeric',
            'address.longitude' => 'nullable|numeric',
            'address.township' => 'required',
            'address.township.slug' => 'required|exists:App\Models\Township,slug',
            'order_items' => 'required|array',
            'order_items.*.slug' => 'required|string|exists:App\Models\Product,slug',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.variation_value_slugs' => 'nullable|array',
            'order_items.*.variation_value_slugs.*' => 'required|exists:App\Models\ProductVariationValue,slug',
        ];

        if ($customerSlug) {
            $rules['customer_slug'] = 'required|string|exists:App\Models\Customer,slug';
        }

        return $request->validate($rules);
    }

    public static function createOrderStatus($orderId, $status = 'pending')
    {
        ShopOrder::where('id', $orderId)->update(['order_status' => $status]);

        $shopOrderVendor = ShopOrderVendor::where('shop_order_id', $orderId);
        $shopOrderVendor->update(['order_status' => $status]);
        $shopOrderVendor = $shopOrderVendor->get();

        foreach ($shopOrderVendor as $vendor) {
            ShopOrderStatus::create([
                'shop_order_vendor_id' => $vendor->id,
                'status' => $status,
            ]);
        }
    }

    public static function createOrderContact($orderId, $customerInfo, $address)
    {
        $customerInfo = array_merge($customerInfo, $address);
        $customerInfo['shop_order_id'] = $orderId;
        $customerInfo['township_id'] = self::getTownshipId($customerInfo['township']['slug']);
        ShopOrderContact::create($customerInfo);
    }

    public static function createShopOrderItem($orderId, $orderItems, $promoCodeId)
    {
        $total = 0;

        foreach ($orderItems as $item) {
            $variations = collect(self::prepareVariations($item['variation_value_slugs']));
            $product = self::getProduct($item['slug']);
            $total += ($product->price + $variations->sum('price')) * $item['quantity'];
        }

        $promoPercentage = 0;

        if ($promoCodeId) {
            $promoPercentage = PromocodeHelper::getPercentage($total, $promoCodeId);
        }

        foreach ($orderItems as $item) {
            $variations = collect(self::prepareVariations($item['variation_value_slugs']));
            $product = self::getProduct($item['slug']);
            $amount = ($product->price + $variations->sum('price')) * $item['quantity'];
            $discount = $amount * $promoPercentage / 100;

            $shop = self::getShopByProduct($item['slug']);

            $shopOrderVendor = self::createShopOrderVendor($orderId, $shop->id);

            $item['shop'] = $shop;
            $item['shop_order_vendor_id'] = $shopOrderVendor->id;
            $item['product_id'] = $product->id;
            $item['shop_id'] = $shop->id;
            $item['product_name'] = $product->name;
            $item['amount'] = $amount;
            $item['variations'] = $variations;
            $item['discount'] = $discount;
            $item['tax'] = ($amount) * $product->tax / 100;

            ShopOrderItem::create($item);
        }
    }

    private static function getTownshipId($slug)
    {
        return Township::where('slug', $slug)->first()->id;
    }

    private static function prepareVariations($variationValueSlugs)
    {
        $variations = [];

        foreach ($variationValueSlugs as $variationValueSlug) {
            $variationValue = self::getProductVariationValue($variationValueSlug);

            $variation = [
                'name' => $variationValue->productVariation->name,
                'value' => $variationValue->value,
                'price' => $variationValue->price,
            ];

            array_push($variations, $variation);
        }

        return $variations;
    }

    private static function createShopOrderVendor($orderId, $shopId)
    {
        return ShopOrderVendor::updateOrCreate(
            ['shop_order_id' => $orderId, 'shop_id' => $shopId],
            ['slug' => StringHelper::generateUniqueSlug()]);
    }

    private static function getProductVariationValue($slug)
    {
        return ProductVariationValue::with('productVariation')->where('slug', $slug)->first();
    }

    private static function getProduct($slug)
    {
        return Product::where('slug', $slug)->first();
    }

    public static function getShopByProduct($slug)
    {
        $product = Product::with('shop')->where('slug', $slug)->firstOrFail();
        return $product->shop;
    }
}
