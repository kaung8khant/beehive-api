<?php

namespace App\Http\Controllers;

use App\Helpers\CollectionHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\ShopOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Jobs\SendSms;
use App\Models\Customer;
use App\Models\Promocode;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopOrderVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShopOrderController extends Controller
{
    use NotificationHelper, PromocodeHelper, ResponseHelper, StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('shop_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        $shopOrders = ShopOrder::with('contact', 'contact.township')
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10)
            ->items();

        return $this->generateResponse($shopOrders, 200);
    }

    public function getShopOrders(Request $request, Shop $shop)
    {
        $sorting = CollectionHelper::getSorting('shop_order_vendors', 'id', $request->by ? $request->by : 'desc', $request->order);

        $vendorOrders = ShopOrderVendor::where('shop_id', $shop->id)
            ->where(function ($query) use ($request) {
                $query->whereHas('shopOrder', function ($q) use ($request) {
                    $q->where('slug', $request->filter);
                })
                    ->orWhereHas('shopOrder.contact', function ($q) use ($request) {
                        $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                            ->orWhere('phone_number', $request->filter);
                    });
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10)
            ->items();

        $result = [];
        foreach ($vendorOrders as $order) {
            $shopOrder = ShopOrder::find($order->shop_order_id)->toArray();
            unset($shopOrder['vendors']);

            $order->shop_order = $shopOrder;
            $order = $order->toArray();

            unset($order['items']);
            $result[] = $order;
        }

        return $this->generateResponse($result, 200);
    }

    public function show(ShopOrder $shopOrder)
    {
        $cache = Cache::get('shopOrder:' . $shopOrder->slug);
        if ($cache) {
            $shopOrder['assign'] = 'pending';
        } else {
            $shopOrder['assign'] = null;
        }
        return $this->generateResponse($shopOrder->load('contact', 'contact.township', 'vendors', 'drivers', 'drivers.status'), 200);
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

        // validate promocode
        if ($validatedData['promo_code_slug']) {
            // may require amount validation.
            $promocode = Promocode::where('slug', $validatedData['promo_code_slug'])->with('rules')->firstOrFail();
            PromocodeHelper::validatePromocodeUsage($promocode, 'shop');
            PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer, 'shop');
            $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'shop');

            $validatedData['promocode_id'] = $promocode->id;
            $validatedData['promocode'] = $promocode->code;
            $validatedData['promocode_amount'] = $promocodeAmount;
        }

        $order = DB::transaction(function () use ($validatedData) {
            $order = ShopOrder::create($validatedData);
            $orderId = $order->id;
            OrderHelper::createOrderContact($orderId, $validatedData['customer_info'], $validatedData['address']);
            OrderHelper::createShopOrderItem($orderId, $validatedData['order_items']);
            OrderHelper::createOrderStatus($orderId);
            return $order;
        });

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

    public function changeStatus(Request $request, ShopOrder $shopOrder)
    {
        if ($shopOrder->order_status === 'delivered' || $shopOrder->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $shopOrder->order_status . '.', 406, true);
        }

        OrderHelper::createOrderStatus($shopOrder->id, $request->status);

        $notificaitonData = $this->notificationData([
            'title' => 'Shop order updated',
            'body' => 'Shop order just has been updated',
            'status' => $request->status,
            'slug' => $shopOrder->slug,
        ]);

        $this->notifyAdmin(
            $notificaitonData
        );

        foreach ($shopOrder->vendors as $vendor) {
            $this->notifyShop(
                $vendor->shop->slug,
                $notificaitonData
            );
        }

        $message = 'Your order has successfully been ' . $request->status . '.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();
        $phoneNumber = Customer::where('id', $shopOrder->customer_id)->first()->phone_number;

        SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData);
        return $this->generateResponse('The order has successfully been ' . $request->status . '.', 200, true);
    }

    private function getCustomerId($slug)
    {
        return Customer::where('slug', $slug)->first()->id;
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

    private function getShop($slug)
    {
        return Shop::where('slug', $slug)->firstOrFail();
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
