<?php

namespace App\Helpers;

use App\Exceptions\BadRequestException;
use App\Helpers\StringHelper;
use App\Jobs\SendSms;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariationValue;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopOrderContact;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderStatus;
use App\Models\ShopOrderVendor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait ShopOrderHelper
{
    public static function validateOrder($request, $customerSlug = false)
    {
        $rules = [
            'slug' => 'required',
            'order_date' => 'nullable',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:pickup,delivery',
            'promo_code' => 'nullable|string|exists:App\Models\Promocode,code',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'address' => 'required',
            'address.house_number' => 'nullable|string',
            'address.floor' => 'nullable|integer|min:0|max:50',
            'address.street_name' => 'nullable|string',
            'address.latitude' => 'nullable|numeric',
            'address.longitude' => 'nullable|numeric',
            'order_items' => 'required|array',
            'order_items.*.slug' => 'required|string|exists:App\Models\Product,slug',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.variation_value_slugs' => 'nullable|array',
            'order_items.*.variation_value_slugs.*' => 'required|exists:App\Models\ProductVariationValue,slug',
        ];

        if ($customerSlug) {
            $rules['customer_slug'] = 'required|string|exists:App\Models\Customer,slug';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        return $validator->validated();
    }

    public static function validateProductVariations($key, $value)
    {
        $product = Product::where('slug', $value['slug'])
            ->with('productVariations')
            ->with('productVariations.productVariationValues')
            ->first();
        if ($product->productVariations()->count() > 0 && empty($value['variation_value_slugs'])) {
            throw new BadRequestException('The order_items.' . $key . '.variation_value_slugs is required.', 400);
        }
        return $product;
    }

    public static function prepareProductVariations($validatedData)
    {
        $orderItems = [];
        $subTotal = 0;
        $tax = 0;
        foreach ($validatedData['order_items'] as $key => $value) {

            // validate variation
            $product = self::validateProductVariations($key, $value);
            // prepare variations for calculation
            $variations = collect(self::prepareVariations($value['variation_value_slugs']));
            // calculate amount, subTotal and tax
            $amount = $product->price + $variations->sum('price');

            $subTotal += ($amount - $product->discount) * $value['quantity'];

            $tax += ($amount - $product->discount) * $product->tax * 0.01 * $value['quantity'];
            $product['price'] = $amount;
            $product['variations'] = $variations;
            $product['quantity'] = $value['quantity'];
            $product['tax'] = ($amount - $product->discount) * $product->tax * 0.01;
            array_push($orderItems, $product->toArray());
        }
        $validatedData['product_id'] = $product['id'];
        $validatedData['order_items'] = $orderItems;
        $validatedData['subTotal'] = $subTotal;
        $validatedData['tax'] = $tax;
        // Log::debug('validatedData => ' . json_encode($validatedData));
        return $validatedData;
    }

    public static function checkVariationsExist($products)
    {
        foreach ($products as $key => $value) {
            $variationsCount = Product::where('slug', $value['slug'])->first()->productVariations()->count();

            if ($variationsCount > 0 && empty($value['variation_value_slugs'])) {
                return 'The order_items.' . $key . '.variation_value_slugs is required.';
            }
        }

        return false;
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
        ShopOrderContact::create($customerInfo);
    }

    public static function createShopOrderItem($orderId, $orderItems)
    {
        foreach ($orderItems as $item) {
            $shop = self::getShopByProduct($item['slug']);
            $shopOrderVendor = self::createShopOrderVendor($orderId, $shop->id);

            $item['shop'] = $shop;
            $item['shop_order_vendor_id'] = $shopOrderVendor->id;
            $item['shop_id'] = $shop->id;
            $item['product_name'] = $item['name'];
            $item['amount'] = $item['price'];

            ShopOrderItem::create($item);
        }
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
        // $shopOrderVendor=ShopOrderVendor::where('shop_order_id', $orderId)->where('shop_id', $shopId)->first();

        // if (isset($shopOrderVendor)) {
        //     return ShopOrderVendor::updateOrCreate(
        //         ['commission' => $commission+$shopOrderVendor->commission],
        //         ['slug' => StringHelper::generateUniqueSlug()]
        //     );
        // } else {
        //     return ShopOrderVendor::create(
        //         ['shop_order_id' => $orderId, 'shop_id' => $shopId,'commission'=>$commission],
        //         ['slug' => StringHelper::generateUniqueSlug()]
        //     );
        // }
        return ShopOrderVendor::updateOrCreate(
            ['shop_order_id' => $orderId, 'shop_id' => $shopId],
            ['slug' => StringHelper::generateUniqueSlug()]
        );
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

    public static function validateOrderV3($request, $customerSlug = false)
    {
        $rules = [
            'slug' => 'required|unique:products',
            'order_date' => 'nullable',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:pickup,delivery',
            'promo_code' => 'nullable|string|exists:App\Models\Promocode,code',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'address' => 'required',
            'address.house_number' => 'nullable|string',
            'address.floor' => 'nullable|integer|min:0|max:50',
            'address.street_name' => 'nullable|string',
            'address.latitude' => 'nullable|numeric',
            'address.longitude' => 'nullable|numeric',
            'order_items' => 'required|array',
            'order_items.*.slug' => 'required|string|exists:App\Models\Product,slug',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.variant_slug' => 'required|exists:App\Models\ProductVariant,slug',
        ];

        if ($customerSlug) {
            $rules['customer_slug'] = 'required|string|exists:App\Models\Customer,slug';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        return $validator->validated();
    }

    public static function prepareProductVariants($validatedData)
    {
        $orderItems = [];
        $subTotal = 0;
        $commission = 0;
        $tax = 0;

        foreach ($validatedData['order_items'] as $key => $value) {
            $productId = Product::where('slug', $value['slug'])->value('id');
            $productVariant = ProductVariant::with('product')->where('slug', $value['variant_slug'])->where('is_enable', 1)->first();

            if ($productId !== $productVariant->product->id) {
                throw new BadRequestException('The order_items.' . $key . '.variant_slug must be part of the product_slug.', 400);
            }

            $item['slug'] = $value['slug'];
            $item['name'] = $productVariant->product->name;
            $item['quantity'] = $value['quantity'];
            $item['price'] = $productVariant->price;
            $item['vendor_price'] = $productVariant->vendor_price;
            $item['tax'] = ($item['price'] - $productVariant->discount) * $productVariant->tax * 0.01;
            $item['discount'] = $productVariant->discount;
            $item['variant'] = $productVariant->variant;
            $item['product_id'] = $productId;
            $item['commission'] = max(($item['price'] - $productVariant->discount - $item['vendor_price']) * $value['quantity'], 0);

            $subTotal += ($item['price'] - $productVariant->discount) * $value['quantity'];

            $commission += max(($item['price'] - $productVariant->discount - $item['vendor_price']) * $value['quantity'], 0);
            $tax += ($item['price'] - $productVariant->discount) * $productVariant->tax * 0.01 * $value['quantity'];

            array_push($orderItems, $item);
        }

        $validatedData['order_items'] = $orderItems;
        $validatedData['subTotal'] = $subTotal;
        $validatedData['commission'] = $commission;
        $validatedData['tax'] = $tax;

        return $validatedData;
    }

    public static function sendAdminPushNotifications()
    {
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin');
        })->pluck('slug');

        $request = new Request();
        $request['slugs'] = $admins;
        $request['message'] = 'A shop order has been received.';

        $appId = config('one-signal.admin_app_id');
        $fields = OneSignalHelper::prepareNotification($request, $appId);

        OneSignalHelper::sendPush($fields, 'admin');
    }

    public static function sendVendorPushNotifications($orderItems)
    {
        $shopIds = array_map(function ($item) {
            return Product::where('slug', $item['slug'])->value('shop_id');
        }, $orderItems);

        $shopIds = array_values(array_unique($shopIds));

        $vendors = User::whereIn('shop_id', $shopIds)->pluck('slug');

        $request = new Request();
        $request['slugs'] = $vendors;
        $request['message'] = 'An order has been received.';

        $appId = config('one-signal.vendor_app_id');
        $fields = OneSignalHelper::prepareNotification($request, $appId);

        return OneSignalHelper::sendPush($fields, 'vendor');
    }

    public static function sendAdminSms()
    {
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin');
        })->pluck('phone_number');

        $message = 'A shop order has been received.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendSms::dispatch($uniqueKey, $admins, $message, 'order', $smsData);
    }

    public static function sendVendorSms($orderItems)
    {
        $vendors = array_map(function ($item) {
            $shopId = Product::where('slug', $item['slug'])->value('shop_id');
            return Shop::where('id', $shopId)->value('notify_numbers');
        }, $orderItems);

        $vendors = array_values(array_unique(array_merge(...$vendors)));

        $message = 'An order has been received.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendSms::dispatch($uniqueKey, $vendors, $message, 'order', $smsData);
    }
}
