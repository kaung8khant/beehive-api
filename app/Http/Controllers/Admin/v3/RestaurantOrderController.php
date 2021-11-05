<?php

namespace App\Http\Controllers\Admin\v3;

use App\Events\OrderAssignEvent;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ServerException;
use App\Helpers\CollectionHelper;
use App\Helpers\OrderAssignHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Helpers\v3\OrderHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\Customer;
use App\Models\Promocode;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Services\MessageService\MessagingService;
use App\Services\PaymentService\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestaurantOrderController extends Controller
{
    use PromocodeHelper, ResponseHelper, StringHelper, OrderAssignHelper;

    protected $messageService;
    protected $paymentService;
    protected $resMes;

    public function __construct(MessagingService $messageService, PaymentService $paymentService)
    {
        $this->messageService = $messageService;
        $this->paymentService = $paymentService;
        $this->resMes = config('response-en.restaurant_order');
    }

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('restaurant_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        $restaurantOrders = RestaurantOrder::exclude(['special_instruction', 'delivery_mode', 'promocode_amount', 'customer_id', 'created_by', 'updated_by'])
            ->with(['RestaurantOrderContact' => function ($query) {
                $query->exclude(['house_number', 'floor', 'street_name', 'latitude', 'longitude']);
            }])
            ->whereBetween('order_date', array($request->from, $request->to))
            ->where(function ($query) use ($request) {
                $query->where('id', ltrim(ltrim($request->filter, 'BHR'), '0'))
                    ->orWhereHas('restaurantOrderContact', function ($q) use ($request) {
                        $q->where('phone_number', $request->filter)
                            ->orWhere('customer_name', 'LIKE', '%' . $request->filter . '%');
                    })
                    ->orWhereHas('restaurant', function ($q) use ($request) {
                        $q->where('name', $request->filter);
                    });
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->get()
            ->map(function ($order) {
                $order->makeHidden(['restaurantOrderItems']);
                $order->restaurant_branch_info = [
                    'slug' => $order->restaurant_branch_info['slug'],
                    'name' => $order->restaurant_branch_info['name'],
                    'restaurant' => [
                        'slug' => $order->restaurant_branch_info['restaurant']['slug'],
                        'name' => $order->restaurant_branch_info['restaurant']['name'],
                    ],
                ];
                return $order;
            });

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function store(Request $request)
    {
        try {
            $request['slug'] = $this->generateUniqueSlug();
            $validatedData = RestaurantOrderHelper::validateOrderV3($request, true);

            if (gettype($validatedData) == 'string') {
                return $this->generateResponse($validatedData, 422, true);
            }

            try {
                $validatedData = RestaurantOrderHelper::prepareRestaurantVariants($validatedData);
            } catch (ForbiddenException $e) {
                return $this->generateResponse($e->getMessage(), 403, true);
            } catch (BadRequestException $e) {
                return $this->generateResponse($e->getMessage(), 400, true);
            }

            $customer = Customer::where('slug', $validatedData['customer_slug'])->first();
            if ($validatedData['promo_code']) {
                try {
                    $validatedData = $this->getPromoData($validatedData, $customer);
                } catch (ForbiddenException $e) {
                    return $this->generateResponse($e->getMessage(), 403, true);
                }
            }

            if ($validatedData['payment_mode'] === 'Credit') {
                $totalAmount = OrderHelper::getTotalAmount($validatedData['order_items'], isset($validatedData['promocode_amount']) ? $validatedData['promocode_amount'] : 0) + $validatedData['delivery_fee'];
                $remainingCredit = OrderHelper::getRemainingCredit($customer);

                if ($totalAmount > $remainingCredit) {
                    return $this->generateResponse('Insufficient credit.', 403, true);
                }

                $validatedData['payment_status'] = 'success';
            }

            $paymentData = [];
            if (!in_array($validatedData['payment_mode'], ['COD', 'Credit'])) {
                try {
                    $paymentData = $this->paymentService->createTransaction($validatedData, 'restaurant');
                } catch (ServerException $e) {
                    return $this->generateResponse($e->getMessage(), 500, true);
                }
            }

            $order = $this->restaurantOrderTransaction($validatedData);

            if ($validatedData['payment_mode'] === 'KPay') {
                $order['prepay_id'] = $paymentData['Response']['prepay_id'];
            } elseif ($validatedData['payment_mode'] === 'CBPay') {
                $order['mer_dqr_code'] = $paymentData['merDqrCode'];
                $order['trans_ref'] = $paymentData['transRef'];
            }

            return $this->generateResponse($order, 201);
        } catch (\Exception $e) {
            $url = explode('/', $request->path());

            if ($url[2] === 'admin' || $url[2] === 'vendor') {
                $auth = $url[2] === 'admin' ? 'Admin' : 'Vendor';
                $phoneNumber = Customer::where('slug', $request->customer_slug)->value('phone_number');
                Log::critical($auth . ' restaurant order ' . $url[1] . ' error: ' . $phoneNumber);
            }

            throw $e;
        }
    }

    public function show(RestaurantOrder $restaurantOrder)
    {
        return $this->generateResponse($restaurantOrder->load('RestaurantOrderContact', 'RestaurantOrderItems', 'restaurantOrderStatuses', 'drivers.driver'), 200);
    }

    public function destroy(RestaurantOrder $restaurantOrder)
    {
        if ($restaurantOrder->order_status === 'delivered' || $restaurantOrder->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $restaurantOrder->order_status . '.', 406, true);
        }

        $message = 'Your order has been cancelled.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();
        $phoneNumber = Customer::where('id', $restaurantOrder->customer_id)->first()->phone_number;

        SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData, $this->messageService);
        RestaurantOrderHelper::createOrderStatus($restaurantOrder->id, 'cancelled');

        return $this->generateResponse('The order has successfully been cancelled.', 200, true);
    }

    private function getPromoData($validatedData, $customer)
    {
        $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest('created_at')->first();
        if (!$promocode) {
            throw new ForbiddenException('Promocode not found.');
        }
        $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'restaurant');
        if (!$validUsage) {
            throw new ForbiddenException('Invalid promocode usage for restaurant.');
        }
        $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer, 'restaurant');

        if (!$validRule) {
            throw new ForbiddenException('Invalid promocode.');
        }
        $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'restaurant');

        $validatedData['promocode_id'] = $promocode->id;
        $validatedData['promocode'] = $promocode->code;
        $validatedData['promocode_amount'] = min($validatedData['subTotal'] + $validatedData['tax'], $promocodeAmount);

        return $validatedData;
    }

    private function restaurantOrderTransaction($validatedData)
    {
        $order = DB::transaction(function () use ($validatedData) {
            $order = RestaurantOrder::create($validatedData);
            RestaurantOrderHelper::createOrderStatus($order->id);
            RestaurantOrderHelper::createOrderContact($order->id, $validatedData['customer_info'], $validatedData['address']);
            RestaurantOrderHelper::createOrderItems($order->id, $validatedData['order_items']);
            return $order->refresh()->load('restaurantOrderContact', 'restaurantOrderItems');
        });

        // assign driver here.
        event(new OrderAssignEvent($order, [], 0));

        $phoneNumber = Customer::where('id', $order->customer_id)->value('phone_number');
        RestaurantOrderHelper::notifySystem($order, $phoneNumber, $this->messageService);

        return $order;
    }

    public function changeStatus(Request $request, RestaurantOrder $restaurantOrder)
    {
        if ($restaurantOrder->order_status === 'delivered' || $restaurantOrder->order_status === 'cancelled') {
            $superUser = Auth::guard('users')->user()->roles->contains('name', 'SuperAdmin');
            if (!$superUser) {
                return $this->generateResponse(sprintf($this->resMes['order_sts_err'], $restaurantOrder->order_status), 406, true);
            }
        }

        if ($restaurantOrder->payment_mode !== 'COD' && $restaurantOrder->payment_status !== 'success') {
            if ($request->status !== 'cancelled') {
                return $this->generateResponse($this->resMes['payment_err'], 406, true);
            }
        }

        RestaurantOrderHelper::createOrderStatus($restaurantOrder->id, $request->status);

        $restaurantOrder['order_status'] = $request->status;
        RestaurantOrderHelper::sendPushNotifications($restaurantOrder, $restaurantOrder->restaurant_branch_id, 'Order Number:' . $restaurantOrder->invoice_id . ', is now ' . $request->status);

        if ($request->status === 'cancelled') {
            $message = 'Your order has been cancelled.';
            $smsData = SmsHelper::prepareSmsData($message);
            $uniqueKey = StringHelper::generateUniqueSlug();
            $phoneNumber = Customer::where('id', $restaurantOrder->customer_id)->first()->phone_number;

            SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData, $this->messageService);
        }

        return $this->generateResponse(sprintf($this->resMes['order_sts_succ'], $request->status), 200, true);
    }

    public function getBranchOrders(Request $request, RestaurantBranch $restaurantBranch)
    {
        $vendorBranchId = Auth::guard('vendors')->user()->restaurant_branch_id;
        if ($vendorBranchId !== $restaurantBranch->id) {
            abort(404);
        }

        $sorting = CollectionHelper::getSorting('restaurant_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact', 'RestaurantOrderItems')
            ->where('restaurant_branch_id', $restaurantBranch->id)
            ->whereBetween('order_date', array($request->from, $request->to))
            ->where(function ($query) use ($request) {
                $query->whereHas('restaurantOrderContact', function ($q) use ($request) {
                    $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                        ->orWhere('phone_number', $request->filter);
                })
                    ->orWhere('id', ltrim(ltrim($request->filter, 'BHR'), '0'));
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->get();

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function cancelOrderItem(RestaurantOrder $restaurantOrder, RestaurantOrderItem $restaurantOrderItem)
    {
        $restaurantOrderItem->delete();
        $restaurantOrder = RestaurantOrder::where('slug', $restaurantOrder->slug)->first();
        $commission = $restaurantOrder->amount * $restaurantOrder->restaurant->commission * 0.01;
        $restaurantOrder->update(['commission' => $commission]);

        return response()->json(['message' => 'Successfully cancelled.'], 200);
    }

    public function updatePayment(Request $request, RestaurantOrder $restaurantOrder)
    {
        $restaurantOrder->update($request->validate([
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'payment_status' => 'required|in:success,failed,pending',
        ]));

        return $this->generateResponse('The order payment has successfully been updated.', 200, true);
    }
}
