<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\NotificationHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\Promocode;
use App\Models\RestaurantOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RestaurantOrderController extends Controller
{
    use NotificationHelper, PromocodeHelper, ResponseHelper, StringHelper;

    public function index(Request $request)
    {

        $customerId = Auth::guard('customers')->user()->id;
        $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact')
            ->with('restaurantOrderContact.township')
            ->with('RestaurantOrderItems')
            ->where('customer_id', $customerId)
            ->latest()
            ->paginate($request->size)
            ->items();

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function show($slug)
    {
        $customerId = Auth::guard('customers')->user()->id;
        $order = RestaurantOrder::with('RestaurantOrderContact')
            ->with('restaurantOrderContact.township')
            ->with('RestaurantOrderItems')
            ->where('slug', $slug)
            ->where('customer_id', $customerId)
            ->firstOrFail();

        return $this->generateResponse($order, 200);
    }

    public function store(Request $request)
    {

        $request['slug'] = $this->generateUniqueSlug();

        $request['customer_slug'] = Auth::guard('customers')->user()->slug;
        // validate order
        $validatedData = OrderHelper::validateOrder($request, true);

        //validate variation
        OrderHelper::checkVariationsExist($validatedData['order_items']);

        // get Customer Info
        $customer = Auth::guard('customers')->user();
        // append customer data
        $validatedData['customer_id'] = Auth::guard('customers')->user()->id;
        // validate and prepare variation
        $validatedData = OrderHelper::prepareRestaurantVariations($validatedData);

        if ($validatedData['promo_code']) {
            // may require amount validation.
            $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest('created_at')->first();
            if (!isset($promocode) && empty($promocode)) {
                throw new BadRequestException("Promocode not found.", 400);
            }
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

        $this->notify(
            $validatedData['restaurant_branch_slug'],
            [
                'title' => 'New Order',
                'body' => "You've just recevied new order. Check now!",
                'type' => 'create',
                'restaurantOrder' => RestaurantOrder::with('RestaurantOrderContact')
                    ->with('restaurantOrderContact.township')
                    ->with('RestaurantOrderItems')
                    ->where('slug', $order->slug)
                    ->firstOrFail(),

            ]);

        return $this->generateResponse(
            $order->refresh()->load('restaurantOrderContact', 'restaurantOrderContact.township', 'restaurantOrderItems'),
            201,
        );
    }

    public function destroy($slug)
    {
        $customer = Auth::guard('customers')->user();
        $customerId = $customer->id;
        $order = RestaurantOrder::where('customer_id', $customerId)->where('slug', $slug)->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, true);
        }

        $this->notify(
            $order->restaurantBranch->slug,
            [
                'title' => 'Order cancelled',
                'body' => "Restaurant order just has been updated",
                'type' => 'update',
                'slug' => $order->slug,
                'status' => 'cancelled',
            ]);

        $message = 'Your order has successfully been cancelled.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendSms::dispatch($uniqueKey, [$customer->phone_number], $message, 'order', $smsData);
        OrderHelper::createOrderStatus($order->id, 'cancelled');

        return $this->generateResponse($message, 200, true);
    }

    private function notify($slug, $data)
    {
        $this->notifyRestaurant(
            $slug,
            [
                'title' => $data['title'] . 'client',
                'body' => $data['body'],
                'data' => [
                    'action' => $data['type'],
                    'type' => 'restaurantOrder',
                    'status' => !empty($data['status']) ? $data['status'] : "",
                    'restaurantOrder' => !empty($data['restaurantOrder']) ? $data['restaurantOrder'] : "",
                    'slug' => !empty($data['slug']) ? $data['slug'] : "",
                ],
            ]
        );

        $this->notifyAdmin(
            [
                'title' => $data['title'] . 'client',
                'body' => $data['body'],
                'data' => [
                    'action' => $data['type'],
                    'type' => 'restaurantOrder',
                    'status' => !empty($data['status']) ? $data['status'] : "",
                    'restaurantOrder' => !empty($data['restaurantOrder']) ? $data['restaurantOrder'] : "",
                    'slug' => !empty($data['slug']) ? $data['slug'] : "",
                ],
            ]
        );
    }
}
