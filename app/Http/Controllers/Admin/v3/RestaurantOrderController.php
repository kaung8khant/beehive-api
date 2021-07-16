<?php

namespace App\Http\Controllers\Admin\v3;

use App\Exceptions\ForbiddenException;
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
use App\Models\User;
use App\Services\MessageService\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestaurantOrderController extends Controller
{
    use PromocodeHelper, ResponseHelper, StringHelper, OrderAssignHelper;

    protected $messageService;

    public function __construct(MessagingService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('restaurant_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        if ($request->filter) {
            $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact', 'RestaurantOrderItems')
                ->whereHas('restaurantOrderContact', function ($q) use ($request) {
                    $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                        ->orWhere('phone_number', $request->filter);
                })
                ->orWhereHas('restaurant', function ($query) use ($request) {
                    $query->where('name', $request->filter);
                })
                ->orWhere('id', ltrim($request->filter, '0'))
                ->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->get();
        } else {
            $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact', 'RestaurantOrderItems')
                ->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->whereBetween('order_date', array($request->from, $request->to))
                ->get();
        }

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
                $validatedData = $this->getPromoData($validatedData, $customer);
            }

            $order = $this->restaurantOrderTransaction($validatedData);

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
        return $this->generateResponse($restaurantOrder->load('RestaurantOrderContact', 'RestaurantOrderItems', 'restaurantOrderStatuses'), 200);
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
            return $this->generateResponse('Promocode not found', 422, true);
        }

        $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'restaurant');
        if (!$validUsage) {
            return $this->generateResponse('Invalid promocode usage for restaurant.', 422, true);
        }

        $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer, 'restaurant');
        if (!$validRule) {
            return $this->generateResponse('Invalid promocode.', 422, true);
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

        $this->assignOrder('restaurant', $order->slug);

        $phoneNumber = Customer::where('id', $order->customer_id)->value('phone_number');
        OrderHelper::notifySystem($order, $phoneNumber, $this->messageService);

        return $order;
    }

    public function changeStatus(Request $request, RestaurantOrder $restaurantOrder)
    {
        $super = User::with('roles')
            ->where('slug', Auth::guard('users')->user()->slug)
            ->whereHas('roles', function ($q) {
                $q->where('name', 'SuperAdmin');
            })
            ->first();

        if (!$super) {
            if ($restaurantOrder->order_status === 'delivered' || $restaurantOrder->order_status === 'cancelled' || !$super) {
                return $this->generateResponse('The order has already been ' . $restaurantOrder->order_status . '.', 406, true);
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

        return $this->generateResponse('The order has successfully been ' . $request->status . '.', 200, true);
    }

    public function getBranchOrders(Request $request, RestaurantBranch $restaurantBranch)
    {
        $vendorBranchId = Auth::guard('vendors')->user()->restaurant_branch_id;
        if ($vendorBranchId !== $restaurantBranch->id) {
            abort(404);
        }

        $sorting = CollectionHelper::getSorting('restaurant_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        if ($request->filter) {
            $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact', 'RestaurantOrderItems')
                ->where('restaurant_branch_id', $restaurantBranch->id)
                ->whereHas('restaurantOrderContact', function ($q) use ($request) {
                    $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                        ->orWhere('phone_number', $request->filter);
                })
                ->orWhere('id', ltrim($request->filter, '0'))
                ->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->get();
        } else {
            $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact', 'RestaurantOrderItems')
                ->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->where('restaurant_branch_id', $restaurantBranch->id)
                ->whereBetween('order_date', array($request->from, $request->to))
                ->get();
        }
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
            $amount = ($item->amount - $item->discount) * $item->quantity;
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
}
