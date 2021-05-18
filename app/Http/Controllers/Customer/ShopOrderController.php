<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\NotificationHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\ShopOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\Customer;
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
        // validate order
        $validatedData = OrderHelper::validateOrder($request, true);
        // get Customer Info
        $customer = Customer::where('slug', $validatedData['customer_slug'])->firstOrFail();
        // append customer data
        $validatedData['customer_id'] = $customer['id'];
        // validate and prepare variation
        $validatedData = OrderHelper::prepareProductVariations($validatedData);

        // TODO:: try catch and rollback if failed.
        $order = ShopOrder::create($validatedData);
        $orderId = $order->id;

        OrderHelper::createOrderContact($orderId, $validatedData['customer_info'], $validatedData['address']);
        OrderHelper::createShopOrderItem($orderId, $validatedData['order_items'], $validatedData, $customer);
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

        $message = 'Your order has successfully been cancelled.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();
        $phoneNumber = Auth::guard('customers')->user()->phone_number;

        SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData);
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
