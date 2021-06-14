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
use App\Models\Promocode;
use App\Models\ShopOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $shopOrder = ShopOrder::with('contact')
            ->with('vendors')
            ->where('customer_id', $customerId)
            ->latest()
            ->paginate($request->size)
            ->items();

        return $this->generateShopOrderResponse($shopOrder, 201, 'array');
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $request['customer_slug'] = Auth::guard('customers')->user()->slug;
        // validate order
        $validatedData = OrderHelper::validateOrder($request, true);

        if (gettype($validatedData) == "string") {
            return $this->generateResponse($validatedData, 422, true);
        }

        // get Customer Info
        $customer = Auth::guard('customers')->user();
        // append customer data
        $validatedData['customer_id'] = $this->customerId;

        // validate and prepare variation
        $validatedData = OrderHelper::prepareProductVariations($validatedData);

        // validate promocode
        if ($validatedData['promo_code']) {
            // may require amount validation.
            $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest('created_at')->first();

            if (!isset($promocode) && empty($promocode)) {
                return $this->generateResponse("Promocode not found", 422, true);
            }

            $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'shop');

            if (!$validUsage) {
                return $this->generateResponse("Invalid promocode usage for shop.", 422, true);
            }

            $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer, 'shop');

            if (!$validRule) {
                return $this->generateResponse("Invalid promocode rule.", 422, true);
            }
            $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'shop');

            $validatedData['promocode_id'] = $promocode->id;
            $validatedData['promocode'] = $promocode->code;
            $validatedData['promocode_amount'] = $promocodeAmount;
        }

        // (transaction) try catch and rollback if failed.
        $order = DB::transaction(function () use ($validatedData) {
            $order = ShopOrder::create($validatedData);
            $orderId = $order->id;
            OrderHelper::createOrderContact($orderId, $validatedData['customer_info'], $validatedData['address']);
            OrderHelper::createShopOrderItem($orderId, $validatedData['order_items']);
            OrderHelper::createOrderStatus($orderId);
            return $order;

        });

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
                            ->with('vendors')
                            ->where('slug', $order->slug)
                            ->firstOrFail()->total_amount,
                        'shop_order' => ShopOrder::with('contact')
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
                        ->with('vendors')
                        ->where('slug', $order->slug)
                        ->firstOrFail(),
                ],
            ]
        );

        return $this->generateShopOrderResponse($order->refresh()->load('contact'), 201);
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

        // SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData);
        OrderHelper::createOrderStatus($shopOrder->id, 'cancelled');

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
