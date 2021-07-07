<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\KbzPayHelper;
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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShopOrderController extends Controller
{
    use NotificationHelper, PromocodeHelper, ResponseHelper, StringHelper;

    protected $customer;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer = Auth::guard('customers')->user();
        }
    }

    public function index(Request $request)
    {
        $shopOrder = ShopOrder::with('contact')
            ->with('vendors')
            ->where('customer_id', $this->customer->id)
            ->latest()
            ->paginate($request->size)
            ->items();

        return $this->generateShopOrderResponse($shopOrder, 201, 'array');
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();
        $validatedData = OrderHelper::validateOrder($request);

        if (gettype($validatedData) == 'string') {
            return $this->generateResponse($validatedData, 422, true);
        }

        $validatedData['customer_id'] = $this->customer->id;
        $validatedData['order_date'] = Carbon::now();
        $validatedData = OrderHelper::prepareProductVariations($validatedData);

        if ($validatedData['promo_code']) {
            $validatedData = $this->getPromoData($validatedData);
        }

        if ($validatedData['payment_mode'] === 'KPay') {
            $kPayData = KbzPayHelper::createKbzPay($validatedData, 'shop');

            if (!$kPayData || $kPayData['Response']['code'] != '0' || $kPayData['Response']['result'] != 'SUCCESS') {
                return $this->generateResponse('Error connecting to KBZ Pay service.', 500, true);
            }
        }

        $order = $this->shopOrderTransaction($validatedData);

        if ($validatedData['payment_mode'] === 'KPay') {
            $order['prepay_id'] = $kPayData['Response']['prepay_id'];
        }

        OrderHelper::notifySystem($order, $validatedData['order_items'], $this->customer->phone_number);

        return $this->generateShopOrderResponse($order, 201);
    }

    public function show($slug)
    {
        $shopOrder = ShopOrder::with('contact')
            ->where('customer_id', $this->customer->id)
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->generateShopOrderResponse($shopOrder, 200);
    }

    public function destroy($slug)
    {
        $shopOrder = ShopOrder::with('vendors')->where('slug', $slug)->where('customer_id', $this->customer->id)->firstOrFail();

        if ($shopOrder->order_status === 'delivered' || $shopOrder->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $shopOrder->order_status . '.', 406, true);
        }

        $message = 'Your order has been cancelled.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();
        $phoneNumber = $this->customer->phone_number;

        SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData);
        OrderHelper::createOrderStatus($shopOrder->id, 'cancelled');

        foreach ($shopOrder->vendors as $vendor) {
            $this->notify($vendor->shop->slug, [
                'title' => 'Order cancelled',
                'body' => 'Your order been cancelled!',
                'action' => 'update',
                'status' => 'cancelled',
                'slug' => $shopOrder->slug,
            ]);
        }

        $this->notifyAdmin(
            [
                'title' => 'Order cancelled',
                'body' => 'Shop order just has been updated',
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

    private function getPromoData($validatedData)
    {
        $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest()->first();
        if (!$promocode) {
            return $this->generateResponse('Promocode not found', 422, true);
        }

        $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'shop');
        if (!$validUsage) {
            return $this->generateResponse('Invalid promocode usage for shop.', 422, true);
        }

        $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $this->customer, 'shop');
        if (!$validRule) {
            return $this->generateResponse('Invalid promocode.', 422, true);
        }

        $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'shop');

        $validatedData['promocode_id'] = $promocode->id;
        $validatedData['promocode'] = $promocode->code;
        $validatedData['promocode_amount'] = $promocodeAmount;

        return $validatedData;
    }

    private function shopOrderTransaction($validatedData)
    {
        $order = DB::transaction(function () use ($validatedData) {
            $order = ShopOrder::create($validatedData);
            OrderHelper::createOrderContact($order->id, $validatedData['customer_info'], $validatedData['address']);
            OrderHelper::createShopOrderItem($order->id, $validatedData['order_items']);
            OrderHelper::createOrderStatus($order->id);
            return $order->refresh()->load('contact');
        });

        $this->notifySystem($validatedData['order_items'], $order->slug);

        return $order;
    }

    private function notifySystem($orderItems, $slug)
    {
        foreach ($orderItems as $item) {
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
                            ->where('slug', $slug)
                            ->firstOrFail()->total_amount,
                        'shop_order' => ShopOrder::with('contact')
                            ->with('vendors')
                            ->where('slug', $slug)
                            ->firstOrFail(),
                    ],
                ]
            );
        }

        $this->notifyAdmin(
            [
                'title' => 'New Order',
                'body' => 'New Order has been received. Check now!',
                'data' => [
                    'action' => 'create',
                    'type' => 'shopOrder',
                    'status' => 'pending',
                    'shopOrder' => ShopOrder::with('contact')
                        ->with('vendors')
                        ->where('slug', $slug)
                        ->firstOrFail(),
                ],
            ]
        );
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
                    'status' => !empty($data['status']) ? $data['status'] : '',
                    'shopOrder' => !empty($data['shopOrder']) ? $data['shopOrder'] : '',
                    'slug' => !empty($data['slug']) ? $data['slug'] : '',
                ],
            ]
        );
    }
}
