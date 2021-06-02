<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\NotificationHelper;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestaurantOrderController extends Controller
{
    use NotificationHelper, PromocodeHelper, ResponseHelper, StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('restaurant_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact', 'restaurantOrderContact.township', 'RestaurantOrderItems')
            ->whereHas('restaurantOrderContact', function ($q) use ($request) {
                $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', $request->filter);
            })
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate($request->size)
            ->items();

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function getBranchOrders(Request $request, RestaurantBranch $restaurantBranch)
    {
        $sorting = CollectionHelper::getSorting('restaurant_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        $restaurantOrders = RestaurantOrder::with('restaurantOrderContact', 'RestaurantOrderItems')
            ->where('restaurant_branch_id', $restaurantBranch->id)
            ->where(function ($query) use ($request) {
                return $query->whereHas('restaurantOrderContact', function ($q) use ($request) {
                    $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                        ->orWhere('phone_number', $request->filter);
                })
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10)
            ->items();

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function show(RestaurantOrder $restaurantOrder)
    {
        return $this->generateResponse($restaurantOrder->load('RestaurantOrderContact', 'restaurantOrderContact.township', 'RestaurantOrderItems'), 200);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();
        // validate order
        $validatedData = OrderHelper::validateOrder($request, true);

        //validate variation
        OrderHelper::checkVariationsExist($validatedData['order_items']);

        // get Customer Info
        $customer = Customer::where('slug', $validatedData['customer_slug'])->firstOrFail();

        // append customer data
        $validatedData['customer_id'] = $this->getCustomerId($validatedData['customer_slug']);

        // validate and prepare variation
        $validatedData = OrderHelper::prepareRestaurantVariations($validatedData);

        if ($validatedData['promo_code_slug']) {
            // may require amount validation.
            $promocode = Promocode::where('slug', $validatedData['promo_code_slug'])->with('rules')->firstOrFail();
            PromocodeHelper::validatePromocodeUsage($promocode, 'restaurant');
            PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer, 'restaurant');
            $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'restaurant');
            $validatedData['promocode_id'] = $promocode->id;
            $validatedData['promocode'] = $promocode->code;
            $validatedData['promocode_amount'] = $promocodeAmount;
        }

        // try catch and rollback if failed.
        $order = DB::transaction(function () use ($validatedData) {
            $order = RestaurantOrder::create($validatedData);
            $orderId = $order->id;

            OrderHelper::createOrderStatus($orderId);

            OrderHelper::createOrderContact($orderId, $validatedData['customer_info'], $validatedData['address']);
            OrderHelper::createOrderItems($orderId, $validatedData['order_items']);
            return $order;
        });

        $this->notify([
            'title' => 'Restaurant order updated',
            'body' => 'Restaurant order just has been updated',
            'status' => $request->status,
            'restaurantOrder' => RestaurantOrder::with('RestaurantOrderContact')
                ->with('restaurantOrderContact.township')
                ->with('RestaurantOrderItems')
                ->where('slug', $order->slug)
                ->firstOrFail(),
            'action' => 'create',
            'slug' => $order->slug,
        ]);

        return $this->generateResponse($order->refresh()->load('restaurantOrderContact', 'restaurantOrderItems'), 201);
    }

    public function destroy(RestaurantOrder $restaurantOrder)
    {
        if ($restaurantOrder->order_status === 'delivered' || $restaurantOrder->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $restaurantOrder->order_status . '.', 406, true);
        }

        $message = 'Your order has successfully been cancelled.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();
        $phoneNumber = Customer::where('id', $restaurantOrder->customer_id)->first()->phone_number;

        SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData);
        OrderHelper::createOrderStatus($restaurantOrder->id, 'cancelled');
        return $this->generateResponse('The order has successfully been cancelled.', 200, true);
    }

    public function changeStatus(Request $request, RestaurantOrder $restaurantOrder)
    {
        if ($restaurantOrder->order_status === 'delivered' || $restaurantOrder->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $restaurantOrder->order_status . '.', 406, true);
        }

        OrderHelper::createOrderStatus($restaurantOrder->id, $request->status);

        $this->notify([
            'title' => 'Restaurant order updated',
            'body' => 'Restaurant order just has been updated',
            'status' => $request->status,
            'slug' => $restaurantOrder->slug,
            'action' => 'update',
        ]);

        $message = 'Your order has successfully been ' . $request->status . '.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();
        $phoneNumber = Customer::where('id', $restaurantOrder->customer_id)->first()->phone_number;

        SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData);
        return $this->generateResponse('The order has successfully been ' . $request->status . '.', 200, true);
    }

    private function getCustomerId($slug)
    {
        return Customer::where('slug', $slug)->first()->id;
    }

    private function notify($data)
    {
        $this->notifyAdmin(
            [
                'title' => $data['title'],
                'body' => $data['body'],
                'data' => [
                    'action' => $data['action'],
                    'type' => 'restaurantOrder',
                    'status' => !empty($data['status']) ? $data['status'] : "",
                    'restaurantOrder' => !empty($data['restaurantOrder']) ? $data['restaurantOrder'] : "",
                    'slug' => !empty($data['slug']) ? $data['slug'] : "",
                ],
            ]
        );
        $this->notifyRestaurant(
            $data['slug'],
            [
                'title' => $data['title'],
                'body' => $data['body'],
                'data' => [
                    'action' => $data['action'],
                    'type' => 'restaurantOrder',
                    'status' => !empty($data['status']) ? $data['status'] : "",
                    'restaurantOrder' => !empty($data['restaurantOrder']) ? $data['restaurantOrder'] : "",
                    'slug' => !empty($data['slug']) ? $data['slug'] : "",
                ],
            ]
        );
    }
}
