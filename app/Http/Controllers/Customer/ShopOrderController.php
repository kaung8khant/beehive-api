<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\NotificationHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\ShopOrderHelper as OrderHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Promocode;
use App\Models\ShopOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopOrderController extends Controller
{
    use NotificationHelper, PromocodeHelper, ResponseHelper, StringHelper;

    protected $customerId;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customerId = Auth::guard('customers')->user()->id;
        }
    }

    public function index(Request $request)
    {
        $customerId = Auth::guard('customers')->user()->id;
        $shopOrder = ShopOrder::where('customer_id', $customerId)->latest()
            ->paginate($request->size)
            ->items();
        return $this->generateShopOrderResponse($shopOrder, 201, 'array');
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = OrderHelper::validateOrder($request);

        $checkVariations = OrderHelper::checkVariationsExist($validatedData['order_items']);
        if ($checkVariations) {
            return $this->generateResponse($checkVariations, 422, true);
        }

        $validatedData['customer_id'] = $this->customerId;
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

        OrderHelper::createOrderContact($orderId, $validatedData['customer_info'], $validatedData['address']);
        OrderHelper::createShopOrderItem($orderId, $validatedData['order_items'], $validatedData['promocode_id']);
        OrderHelper::createOrderStatus($orderId);

        foreach ($validatedData['order_items'] as $item) {
            $this->notify(
                OrderHelper::getShopByProduct($item['slug'])->slug,
                [
                    'title' => 'New Order',
                    'body' => "You've just recevied new order. Check now!",
                    'action' => 'create',
                    'status' => 'pending',
                    'shopOrder' => [
                        'slug' => OrderHelper::getShopByProduct($item['slug'])->slug,
                        'order_status' => 'pending',
                        'total_amount' => ShopOrder::with('contact')
                            ->with('contact.township')
                            ->with('vendors')
                            ->where('slug', $order->slug)
                            ->firstOrFail()->total_amount,
                        'shop_order' => ShopOrder::with('contact')
                            ->with('contact.township')
                            ->with('vendors')
                            ->where('slug', $order->slug)
                            ->firstOrFail(),
                    ],
                ]);
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

    public function show($slug)
    {
        $customerId = Auth::guard('customers')->user()->id;
        $shopOrder = ShopOrder::where('slug', $slug)->where('customer_id', $customerId)
            ->with('contact')
            ->firstOrFail();

        return $this->generateShopOrderResponse($shopOrder, 200);
    }

    public function destroy($slug)
    {
        $shopOrder = ShopOrder::with('vendors')->where('slug', $slug)->where('customer_id', $this->customerId)->firstOrFail();

        if ($shopOrder->order_status === 'delivered' || $shopOrder->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $shopOrder->order_status . '.', 406, true);
        }

        OrderHelper::createOrderStatus($shopOrder->id, 'cancelled');

        $shopOrder = ShopOrder::with('vendors')->where('slug', $slug)->where('customer_id', $this->customerId)->firstOrFail();

        foreach ($shopOrder->vendors as $vendor) {
            $this->notify($vendor->shop->slug, [
                'title' => 'Order cancelled',
                'body' => "Your order been cancelled!",
                'action' => 'update',
                'status' => 'cancelled',
                'slug' => $shopOrder->slug,
            ]);
        }

        $this->notifyAdmin(
            [
                'title' => "Order cancelled",
                'body' => "Shop order just has been updated",
                'data' => [
                    'action' => 'update',
                    'type' => 'shopOrder',
                    'status' => 'cancelled',
                    'slug' => $shopOrder->slug,
                ],
            ]
        );

        return $this->generateResponse($shopOrder->order_status, 200);
    }

    private function notify($slug, $data)
    {
        $this->notifyShop(
            $slug,
            [
                'title' => $data['title'],
                'body' => $data['body'],
                'data' => [
                    'action' => $data['action'],
                    'type' => 'shopOrder',
                    'status' => !empty($data['status']) ? $data['status'] : "",
                    'shopOrder' => !empty($data['shopOrder']) ? $data['shopOrder'] : "",
                    'slug' => !empty($data['slug']) ? $data['slug'] : "",
                ],
            ]
        );

    }
}
