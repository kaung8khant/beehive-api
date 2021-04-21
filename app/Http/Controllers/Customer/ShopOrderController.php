<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\NotificationHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariationValue;
use App\Models\Promocode;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopOrderContact;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderStatus;
use App\Models\ShopOrderVendor;
use App\Models\Township;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopOrderController extends Controller
{
    use StringHelper, ResponseHelper, NotificationHelper, PromocodeHelper;

    protected $customerId;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customerId = Auth::guard('customers')->user()->id;
        }
    }

    public function index(Request $request)
    {
        $shopOrder = ShopOrder::where('customer_id', $this->customerId)
            ->latest()
            ->paginate($request->size)
            ->items();

        return $this->generateShopOrderResponse($shopOrder, 201, 'array');
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $this->validateOrder($request);
        $validatedData['customer_id'] = $this->customerId;
        $validatedData['promocode_id'] = null;

        if ($validatedData['promo_code_slug']) {
            $isPromoValid = $this->validatePromo($validatedData['promo_code_slug']);
            if (!$isPromoValid) {
                return $this->generateResponse('Invalid promo code.', 406, true);
            }

            $validatedData['promocode_id'] = Promocode::where('slug', $validatedData['promo_code_slug'])->first()->id;
        }

        $order = ShopOrder::create($validatedData);
        $orderId = $order->id;

        $this->createOrderContact($orderId, $validatedData['customer_info'], $validatedData['address']);

        $this->createShopOrderItem($orderId, $validatedData['order_items'], $validatedData['promocode_id']);

        $this->createOrderStatus($orderId);

        foreach ($validatedData['order_items'] as $item) {
            $this->notify($this->getShop($item['slug'])->id, ['title' => 'New Order', 'body' => "You've just recevied new order. Check now!"]);
        }

        return $this->generateShopOrderResponse($order->refresh(), 201);
    }

    public function show($slug)
    {
        $shopOrder = ShopOrder::with('contact')
            ->where('slug', $slug)
            ->where('customer_id', $this->customerId)
            ->firstOrFail();

        return $this->generateShopOrderResponse($shopOrder, 200);
    }

    public function destroy($slug)
    {

        $shopOrder = ShopOrder::with('vendors')->where('slug', $slug)->where('customer_id', $this->customerId)->firstOrFail();

        if ($shopOrder->order_status === 'delivered' || $shopOrder->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $shopOrder->order_status . '.', 406, true);
        }

        $this->createOrderStatus($shopOrder->id, 'cancelled');

        $shopOrder = ShopOrder::with('vendors')->where('slug', $slug)->where('customer_id', $this->customerId)->firstOrFail();

        return $this->generateResponse($shopOrder->order_status, 200);
    }

    private function validateOrder(Request $request)
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
            'order_items.*.slug' => 'required|string',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.variation_value_slugs' => 'nullable|array',
            'order_items.*.variation_value_slugs.*' => 'required|exists:App\Models\ProductVariationValue,slug',
        ];

        return $request->validate($rules);
    }

    private function getShopOrderId($slug)
    {
        return ShopOrder::where('slug', $slug)->firstOrFail()->id;
    }

    private function createOrderStatus($orderId, $status = 'pending')
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
    private function createOrderContact($orderId, $customerInfo, $address)
    {
        $customerInfo = array_merge($customerInfo, $address);
        $customerInfo['shop_order_id'] = $orderId;
        $customerInfo['township_id'] = $this->getTownshipId($customerInfo['township']['slug']);
        ShopOrderContact::create($customerInfo);
    }

    private function createShopOrderItem($orderId, $orderItems, $promoCodeId)
    {
        $total = 0;

        foreach ($orderItems as $item) {
            $variations = collect($this->prepareVariations($item['variation_value_slugs']));
            $product = $this->getProduct($item['slug']);
            $total += ($product->price + $variations->sum('price')) * $item['quantity'];
        }

        $promoPercentage = 0;

        if ($promoCodeId) {
            $promoPercentage = $this->getPercentage($total, $promoCodeId);
        }

        foreach ($orderItems as $item) {
            $variations = collect($this->prepareVariations($item['variation_value_slugs']));
            $product = $this->getProduct($item['slug']);
            $amount = ($product->price + $variations->sum('price')) * $item['quantity'];
            $discount = $amount * $promoPercentage / 100;

            $shop = $this->getShop($item['slug']);

            $shopOrderVendor = $this->createShopOrderVendor($orderId, $shop->id);

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

    private function createShopOrderVendor($orderId, $shopId)
    {
        return ShopOrderVendor::updateOrCreate(
            ['shop_order_id' => $orderId, 'shop_id' => $shopId],
            ['slug' => $this->generateUniqueSlug()]);
    }

    private function prepareVariations($variationValueSlugs)
    {
        $variations = [];

        foreach ($variationValueSlugs as $variationValueSlug) {
            $variationValue = $this->getMenuVariationValue($variationValueSlug);

            $variation = [
                'name' => $variationValue->productVariation->name,
                'value' => $variationValue->value,
                'price' => $variationValue->price,
            ];

            array_push($variations, $variation);
        }

        return $variations;
    }

    private function getMenuVariationValue($slug)
    {
        return ProductVariationValue::with('productVariation')->where('slug', $slug)->first();
    }

    private function getTownshipId($slug)
    {
        return Township::where('slug', $slug)->first()->id;
    }

    private function getProduct($slug)
    {
        return Product::where('slug', $slug)->first();
    }

    private function getShop($slug)
    {
        $product = Product::with('shop')->where('slug', $slug)->firstOrFail();
        return $product->shop;
    }

    private function notify($slug, $data)
    {
        $this->notifyShop(
            $slug,
            [
                'title' => $data['title'],
                'body' => $data['body'],
                'img' => '',
                'data' => [
                    'action' => '',
                    'type' => 'notification',
                ],
            ]
        );
    }
}
