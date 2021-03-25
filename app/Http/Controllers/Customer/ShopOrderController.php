<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\NotificationHelper;
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
use App\Models\Township;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopOrderController extends Controller
{
    use StringHelper, ResponseHelper, NotificationHelper;

    protected $customer_id;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer_id = Auth::guard('customers')->user()->id;
        }
    }

    public function index(Request $request)
    {
        $shopOrder = ShopOrder::with('contact', 'items', 'status')->where("customer_id", $this->customer_id)->paginate()->items();
        return $this->generateResponse($shopOrder, 201);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $this->validateOrder($request);
        $validatedData['customer_id'] = $this->customer_id;

        if (!empty($validatedData["promo_code_slug"])) {
            $validatedData['promocode_id'] = Promocode::where("slug", $validatedData["promo_code_slug"])->firstOrFail()->id;
        }

        $order = ShopOrder::create($validatedData);
        $orderId = $order->id;

        $this->createOrderStatus($orderId);

        $this->createOrderContact($orderId, $validatedData['customer_info'], $validatedData['address']);

        $this->createOrderItems($orderId, $validatedData['order_items'], $request->order_type);

        foreach ($validatedData['order_items'] as $item) {
            $this->notify($this->getShop($item['slug'])->id, ['title' => 'New Order', 'body' => "You've just recevied new order. Check now!"]);
        }

        return $this->generateResponse($order->refresh(), 201);
    }

    public function show($slug)
    {
        $shop = ShopOrder::where('slug', $slug)
            ->with('contact', 'items')
            ->firstOrFail();
        return $this->generateResponse($shop, 200);
    }

    public function destroy($slug)
    {

        $shopOrder = ShopOrder::where('slug', $slug)->where('customer_id', $this->customer_id)->firstOrFail();

        $shopOrderStatus = ShopOrderStatus::where('shop_order_id', $shopOrder->id)->firstOrFail();

        if ($shopOrderStatus->status === 'delivered' || $shopOrderStatus->status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $shopOrderStatus->status . '.', 406, true);
        }

        $shopOrderStatus->status = "cancelled";
        $shopOrderStatus->update();

        return $this->generateResponse($shopOrderStatus, 200);
    }

    private function validateOrder(Request $request)
    {
        $rules = [
            'slug' => 'required',
            'order_date' => 'required|date_format:Y-m-d',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:package,delivery',
            'promo_code_slug' => 'nullable',
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
            'address.township.slug' => 'required',
            'order_items' => 'required|array',
            'order_items.*.slug' => 'required|string',
            'order_items.*.quantity' => 'required|integer',
        ];

        return $request->validate($rules);
    }

    private function getShopOrderId($slug)
    {
        return ShopOrder::where('slug', $slug)->firstOrFail()->id;
    }

    private function createOrderStatus($orderId, $status = 'pending')
    {
        $shop = ShopOrderStatus::create([
            'shop_order_id' => $orderId,
            'status' => $status,
        ]);
    }
    private function createOrderContact($orderId, $customerInfo, $address)
    {
        $customerInfo = array_merge($customerInfo, $address);
        $customerInfo['shop_order_id'] = $orderId;
        $customerInfo['township_id'] = $this->getTownshipId($customerInfo['township']['slug']);
        ShopOrderContact::create($customerInfo);
    }

    private function createOrderItems($orderId, $orderItems, $orderType)
    {

        foreach ($orderItems as $item) {
            $variations = collect($this->prepareVariations($item['variation_value_slugs']));
            $product = $this->getProduct($item['slug']);
            $item['shop'] = $this->getShop($item['slug']);
            $item['shop_order_id'] = $orderId;
            $item['product_id'] = $product->id;
            $item['product_name'] = $product->name;
            $item['amount'] = $product->price + $variations->sum('price');
            $item['variations'] = $variations;
            $item['discount'] = ($product->price + $variations->sum('price')) * 5 / 100;
            $item['tax'] = ($item['amount'] - $item['discount']) * 5 / 100;

            ShopOrderItem::create($item);
        }
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
        $product = Product::with("shop")->where('slug', $slug)->firstOrFail();
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
