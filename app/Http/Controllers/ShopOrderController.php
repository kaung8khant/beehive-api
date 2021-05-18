<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\ShopOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Jobs\SendSms;
use App\Models\Customer;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopOrderVendor;
use Illuminate\Http\Request;

class ShopOrderController extends Controller
{
    use NotificationHelper, PromocodeHelper, ResponseHelper, StringHelper;

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
        $shopId = $this->getShop($slug)->id;

        $vendorOrders = ShopOrderVendor::where('shop_id', $shopId)
            ->where(function ($query) use ($request) {
                $query->whereHas('shopOrder', function ($q) use ($request) {
                    $q->where('slug', $request->filter);
                })
                    ->orWhereHas('shopOrder.contact', function ($q) use ($request) {
                        $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                            ->orWhere('phone_number', $request->filter);
                    });
            })
            ->latest()
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

    public function show($slug)
    {
        $shopOrder = ShopOrder::with('contact')
            ->with('contact.township')
            ->with('vendors')
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->generateResponse($shopOrder, 200);
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
            PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer);
            $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal']);

            $validatedData['promocode_id'] = $promocode->id;
            $validatedData['promocode'] = $promocode->code;
            $validatedData['promocode_amount'] = $promocodeAmount;
        }

        // TODO:: try catch and rollback if failed.
        $order = ShopOrder::create($validatedData);
        $orderId = $order->id;

        OrderHelper::createOrderContact($orderId, $validatedData['customer_info'], $validatedData['address']);
        OrderHelper::createShopOrderItem($orderId, $validatedData['order_items']);
        OrderHelper::createOrderStatus($orderId);

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

    public function changeStatus(Request $request, $slug)
    {
        $order = ShopOrder::where('slug', $slug)->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, true);
        }

        OrderHelper::createOrderStatus($order->id, $request->status);

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

        $message = 'Your order has successfully been ' . $request->status . '.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();
        $phoneNumber = Customer::where('id', $order->customer_id)->first()->phone_number;

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
