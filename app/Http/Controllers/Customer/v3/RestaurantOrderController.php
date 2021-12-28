<?php

namespace App\Http\Controllers\Customer\v3;

use App\Events\OrderAssignEvent;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ServerException;
use App\Helpers\GeoHelper;
use App\Helpers\OrderAssignHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Helpers\v3\PromocodeHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\Promocode;
use App\Models\RestaurantOrder;
use App\Services\MessageService\MessagingService;
use App\Services\PaymentService\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestaurantOrderController extends Controller
{
    use PromocodeHelper, ResponseHelper, StringHelper, OrderAssignHelper;

    protected $customer;
    protected $messageService;
    protected $paymentService;

    public function __construct(MessagingService $messageService, PaymentService $paymentService)
    {
        if (Auth::guard('customers')->check()) {
            $this->customer = Auth::guard('customers')->user();
        }

        $this->messageService = $messageService;
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact')
            ->with('RestaurantOrderItems')
            ->where('customer_id', $this->customer->id)
            ->orderBy('id', 'desc')
            ->paginate($request->size)
            ->items();

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function store(Request $request)
    {
        try {
            $request['slug'] = $this->generateUniqueSlug();
            $validatedData = OrderHelper::validateOrderV3($request);

            if (gettype($validatedData) == 'string') {
                return $this->generateResponse($validatedData, 422, true);
            }

            try {
                $validatedData = OrderHelper::prepareRestaurantVariants($validatedData);
            } catch (ForbiddenException $e) {
                return $this->generateResponse($e->getMessage(), 403, true);
            }

            try {
                OrderHelper::checkOpeningTime($validatedData['restaurant_branch_slug']);
            } catch (ForbiddenException $e) {
                return $this->generateResponse($e->getMessage(), 403, true);
            }

            if ($validatedData['promo_code']) {
                try {
                    $validatedData = $this->getPromoData($validatedData);
                } catch (ForbiddenException $e) {
                    return $this->generateResponse($e->getMessage(), 403, true);
                }
            }

            $paymentData = [];
            if ($validatedData['payment_mode'] !== 'COD') {
                try {
                    $paymentData = $this->paymentService->createTransaction($validatedData, 'restaurant');
                } catch (ServerException $e) {
                    return $this->generateResponse($e->getMessage(), 500, true);
                }
            }

            $order = $this->restaurantOrderTransaction($validatedData);

            if ($validatedData['payment_mode'] === 'KPay') {
                $order['prepay_id'] = $paymentData['Response']['prepay_id'];
            } else if ($validatedData['payment_mode'] === 'CBPay') {
                $order['mer_dqr_code'] = $paymentData['merDqrCode'];
                $order['trans_ref'] = $paymentData['transRef'];
            }

            return $this->generateResponse($order, 201);
        } catch (\Exception $e) {
            Log::critical('Customer restaurant order v3 error: ' . Auth::guard('customers')->user()->phone_number);
            throw $e;
        }
    }

    public function show($slug)
    {
        $order = RestaurantOrder::with('RestaurantOrderContact')
            ->exclude(['created_by', 'updated_by'])
            ->with('RestaurantOrderItems')
            ->where('slug', $slug)
            ->where('customer_id', $this->customer->id)
            ->firstOrFail();

        $distance = GeoHelper::calculateDistance($order->restaurantOrderContact->latitude, $order->restaurantOrderContact->longitude, $order->restaurant_branch_info['latitude'], $order->restaurant_branch_info['longitude']);
        $order->delivery_time = GeoHelper::calculateDeliveryTime($distance);

        return $this->generateResponse($order, 200);
    }

    public function destroy($slug)
    {
        return $this->generateResponse('You cannot cancel order at the moment. Please contact support.', 403, true);

        $order = RestaurantOrder::where('slug', $slug)
            ->where('customer_id', $this->customer->id)
            ->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, true);
        }

        $message = 'Your order has been cancelled.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendSms::dispatch($uniqueKey, [$this->customer->phone_number], $message, 'order', $smsData, $this->messageService);
        OrderHelper::createOrderStatus($order, 'cancelled');

        return $this->generateResponse($message, 200, true);
    }

    private function getPromoData($validatedData)
    {
        $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest('created_at')->first();
        if (!$promocode) {
            throw new ForbiddenException('Promocode not found.');
        }

        $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'restaurant');
        if (!$validUsage) {
            throw new ForbiddenException('Invalid promocode usage for restaurant.');
        }

        $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $this->customer, 'restaurant');
        if (!$validRule) {
            throw new ForbiddenException('Invalid promocode.');
        }

        $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'restaurant');

        $validatedData['promocode_id'] = $promocode->id;
        $validatedData['promocode'] = $promocode->code;
        $validatedData['promocode_amount'] = $promocodeAmount;

        return $validatedData;
    }

    private function restaurantOrderTransaction($validatedData)
    {
        $order = DB::transaction(function () use ($validatedData) {
            $order = RestaurantOrder::create($validatedData);
            OrderHelper::createOrderStatus($order);
            OrderHelper::createOrderContact($order->id, $validatedData['customer_info'], $validatedData['address']);
            OrderHelper::createOrderItems($order->id, $validatedData['order_items']);
            return $order->refresh()->load('restaurantOrderContact', 'restaurantOrderItems');
        });

        event(new OrderAssignEvent($order, [], 0));

        // $this->assignOrder('restaurant', $order->slug);

        OrderHelper::notifySystem($order, $this->customer->phone_number, $this->messageService);

        return $order;
    }
}
