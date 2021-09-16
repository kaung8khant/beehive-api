<?php

namespace App\Http\Controllers\Admin\v3;

use App\Events\OrderAssignEvent;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ServerException;
use App\Helpers\CollectionHelper;
use App\Helpers\OrderAssignHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\Customer;
use App\Models\Promocode;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Services\MessageService\MessagingService;
use App\Services\PaymentService\PaymentService;
use Exception;
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

        $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact', 'RestaurantOrderItems')
            ->whereBetween('order_date', array($request->from, $request->to))
            ->where(function ($query) use ($request) {
                $query->whereHas('restaurantOrderContact', function ($q) use ($request) {
                    $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                        ->orWhere('phone_number', $request->filter);
                })
                    ->orWhereHas('restaurant', function ($query) use ($request) {
                        $query->where('name', $request->filter);
                    })
                    ->orWhere('id', ltrim(ltrim($request->filter, 'BHR'), '0'));
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->get();

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function store(Request $request)
    {
        try {
            $request['slug'] = $this->generateUniqueSlug();
            $validatedData = OrderHelper::validateOrderV3($request, true);

            if (gettype($validatedData) == 'string') {
                return $this->generateResponse($validatedData, 422, true);
            }

            try {
                $validatedData = OrderHelper::prepareRestaurantVariants($validatedData);
            } catch (ForbiddenException $e) {
                return $this->generateResponse($e->getMessage(), 403, true);
            }

            $customer = Customer::where('slug', $validatedData['customer_slug'])->first();

            if ($validatedData['promo_code']) {
                try {
                    $validatedData = $this->getPromoData($validatedData, $customer);
                } catch (Exception $e) {
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
            } elseif ($validatedData['payment_mode'] === 'CBPay') {
                $order['mer_dqr_code'] = $paymentData['merDqrCode'];
                $order['trans_ref'] = $paymentData['transRef'];
            }

            return $this->generateResponse($order, 201);
        } catch (\Exception $e) {
            $url = explode('/', $request->path());
            $auth = $url[2] === 'admin' ? 'Admin' : 'Vendor';
            $phoneNumber = Customer::where('slug', $request->customer_slug)->value('phone_number');

            Log::critical($auth . ' restaurant order ' . $url[1] . ' error: ' . $phoneNumber);
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
        OrderHelper::createOrderStatus($restaurantOrder->id, 'cancelled');

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
        $validatedData['promocode_amount'] = $promocodeAmount;

        return $validatedData;
    }

    private function restaurantOrderTransaction($validatedData)
    {
        $order = DB::transaction(function () use ($validatedData) {
            $order = RestaurantOrder::create($validatedData);
            OrderHelper::createOrderStatus($order->id);
            OrderHelper::createOrderContact($order->id, $validatedData['customer_info'], $validatedData['address']);
            OrderHelper::createOrderItems($order->id, $validatedData['order_items']);
            return $order->refresh()->load('restaurantOrderContact', 'restaurantOrderItems');
        });

        // assign driver here.
        // $this->assignOrder('restaurant', $order->slug);

        event(new OrderAssignEvent($order, [], 0));

        $phoneNumber = Customer::where('id', $order->customer_id)->value('phone_number');
        OrderHelper::notifySystem($order, $phoneNumber, $this->messageService);

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

        OrderHelper::createOrderStatus($restaurantOrder->id, $request->status);

        $restaurantOrder['order_status'] = $request->status;
        OrderHelper::sendPushNotifications($restaurantOrder, $restaurantOrder->restaurant_branch_id, 'Order Number:' . $restaurantOrder->invoice_id . ', is now ' . $request->status);

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

        $promocode = Promocode::where('code', $restaurantOrder->promocode)->first();
        $orderItems = $restaurantOrder->restaurantOrderItems;
        $subTotal = 0;
        $commission = 0;

        foreach ($orderItems as $item) {
            $amount = ($item->amount) * $item->quantity;
            $subTotal += $amount;
            $commission += $item->commission;
        }

        $commission = $subTotal * $restaurantOrder->restaurant->commission * 0.01;

        if ($promocode) {
            if ($promocode->type === 'fix') {
                $restaurantOrder->update(['promocode_amount' => $promocode->amount, 'commission' => $commission]);
            } else {
                $restaurantOrder->update(['promocode_amount' => $subTotal * $promocode->amount * 0.01, 'commission' => $commission]);
            }
        }

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
