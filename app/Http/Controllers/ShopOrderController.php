<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Models\ShopOrder;
use App\Models\ShopOrderStatus;
use App\Models\ShopOrderVendor;
use Illuminate\Http\Request;

class ShopOrderController extends Controller
{
    use ResponseHelper, StringHelper, NotificationHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/shop-orders",
     *      operationId="getShopOrderLists",
     *      tags={"Shop Orders"},
     *      summary="Get list of shop orders",
     *      description="Returns list of shop orders",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="filter",
     *          description="Filter",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function index()
    {
        $shopOrders = ShopOrder::with('contact')
            ->with('contact.township')
            ->latest()
            ->paginate(10)
            ->items();

        return $this->generateResponse($shopOrders, 200);
    }

    public function getShopOrders(Request $request, $slug)
    {
        $shopOrders = ShopOrder::with('contact')->with('vendors')
        // ->whereDate('order_date', '>=', $request->from)
        // ->whereDate('order_date', '<=', $request->to)
            ->where('slug', $slug)
            ->whereHas('contact', function ($q) use ($request) {
                $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', $request->filter);
            })->orWhere('slug', $request->filter)
            ->latest()
            ->paginate(10)
            ->items();

        return $this->generateResponse($shopOrders, 200);
    }

    public function show($slug)
    {
        $shop = ShopOrder::with('contact')
            ->with('contact.township')
            ->with('vendors')
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->generateResponse($shop, 200);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $this->validateOrder($request);
        $validatedData['customer_id'] = $this->getCustomerId($validatedData['customer_slug']);
        $validatedData['promocode_id'] = null;

        if ($validatedData['promo_code_slug']) {
            $isPromoValid = $this->validatePromo($validatedData['promo_code_slug'], $validatedData['customer_id'], 'shop');
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
            $this->notify($this->getShopByProduct($item['slug'])->id,
                [
                    'title' => 'New Order',
                    'body' => "You've just recevied new order. Check now!",
                ]
            );
        }

        $this->notifyAdmin(
            [
                'title' => "New Order",
                'body' => "New Order has been received. Check now!",
                'data' => [
                    'action' => 'create',
                    'type' => 'shopOrder',
                    'status' => 'pending',
                    'shopOrder' => ShopOrder::with('contact')
                        ->with('contact.township')
                        ->with('vendors')
                        ->where('slug', $order->slug)
                        ->firstOrFail(),
                ],
            ]
        );

        return $this->generateShopOrderResponse($order->refresh(), 201);
    }

    private function validateOrder($request)
    {
        $rules = [
            'slug' => 'required',
            'order_date' => 'required|date_format:Y-m-d',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:pickup,delivery',
            'customer_slug' => 'required|string|exists:App\Models\Customer,slug',
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

    public function changeStatus(Request $request, $slug)
    {

        $order = ShopOrder::where('slug', $slug)->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, true);
        }

        $this->createOrderStatus($order->id, $request->status);

        $notificaitonData = $this->notificationData([
            'title' => 'Shop order updated',
            'body' => 'Shop order just has been updated',
            'status' => $request->status,
            'slug' => $slug,
        ]);

        $this->notifyAdmin(
            $notificaitonData
        );

        foreach ($order->vendors as $vendor) {

            $this->notifyShop(
                $vendor->shop->slug,
                $notificaitonData
            );
        }

        return $this->generateResponse('The order has successfully been ' . $request->status . '.', 200, true);
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
    private function notificationData($data)
    {
        return [
            'title' => $data['title'],
            'body' => $data['body'],
            'img' => '',
            'data' => [
                'action' => 'update',
                'type' => 'shopOrder',
                'status' => $data['status'],
                'slug' => $data['slug'],

            ],
        ];

    }
}
