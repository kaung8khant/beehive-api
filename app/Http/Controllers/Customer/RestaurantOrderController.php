<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\ForbiddenException;
use App\Exceptions\ServerException;
use App\Helpers\OrderAssignHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\Menu;
use App\Models\MenuVariation;
use App\Models\MenuVariationValue;
use App\Models\Promocode;
use App\Models\RestaurantOrder;
use App\Services\MessageService\MessagingService;
use App\Services\PaymentService\PaymentService;
use Carbon\Carbon;
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
        $customerId = Auth::guard('customers')->user()->id;
        $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact')
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
            ->with('RestaurantOrderItems')
            ->where('slug', $slug)
            ->where('customer_id', $customerId)
            ->firstOrFail();

        return $this->generateResponse($order, 200);
    }

    public function store(Request $request)
    {
        // validate request parameters. (HTTP)
        // validate persistent data.
        // create order
        // - order create
        // - trigger order status event. (Status: pending)
        // - trigger payment status event. (Status: pending)
        // - trigger sms notification event.
        // - trigger one signal notification to customer.
        // - trigger one signal notification to admins.
        // - trigger one signal notification to vendrs.
        // - repository layer assign driver (order object) // business logic
        // - find active drivers from firebase.
        // - validate active drivers against database. such as enable/disable
        // - filter location based on restaurant and drivers.
        // - trigger event to assign this driver.
        // - assign recursively until 5 dirvers.
        // - if last driver doesn't accept order, we will notifiy admins.
        try {
            $request['slug'] = $this->generateUniqueSlug();
            $validatedData = OrderHelper::validateOrder($request);

            if (gettype($validatedData) == 'string') {
                return $this->generateResponse($validatedData, 422, true);
            }

            try {
                OrderHelper::checkOpeningTime($validatedData['restaurant_branch_slug']);
            } catch (ForbiddenException $e) {
                return $this->generateResponse($e->getMessage(), 403, true);
            }

            try {
                $this->fixVariation($validatedData['order_items']);
            } catch (\Exception $e) {
                Log::channel('slack')->info('-----Variation Fix Error-----' . $e);
            }

            $validatedData['customer_id'] = $this->customer->id;
            $validatedData['order_date'] = Carbon::now();
            $validatedData = OrderHelper::prepareRestaurantVariations($validatedData);

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
            } elseif ($validatedData['payment_mode'] === 'CBPay') {
                $order['mer_dqr_code'] = $paymentData['merDqrCode'];
                $order['trans_ref'] = $paymentData['transRef'];
            }

            return $this->generateResponse($order, 201);
        } catch (\Exception $e) {
            Log::critical('Customer restaurant order v2 error: ' . Auth::guard('customers')->user()->phone_number);
            throw $e;
        }
    }

    public function destroy($slug)
    {
        return $this->generateResponse('You cannot cancel order at the moment. Please contact support.', 403, true);

        $customer = Auth::guard('customers')->user();
        $customerId = $customer->id;
        $order = RestaurantOrder::where('customer_id', $customerId)->where('slug', $slug)->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, true);
        }

        $message = 'Your order has been cancelled.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendSms::dispatch($uniqueKey, [$customer->phone_number], $message, 'order', $smsData, $this->messageService);
        OrderHelper::createOrderStatus($order->id, 'cancelled');

        return $this->generateResponse($message, 200, true);
    }

    private function getPromoData($validatedData)
    {
        $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest('created_at')->first();
        if (!isset($promocode) && empty($promocode)) {
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
            OrderHelper::createOrderStatus($order->id);
            OrderHelper::createOrderContact($order->id, $validatedData['customer_info'], $validatedData['address']);
            OrderHelper::createOrderItems($order->id, $validatedData['order_items']);
            return $order->refresh()->load('restaurantOrderContact', 'restaurantOrderItems');
        });

        $this->assignOrder('restaurant', $order->slug);

        OrderHelper::notifySystem($order, $this->customer->phone_number, $this->messageService);

        return $order;
    }

    private function fixVariation($orderItems)
    {
        $menuSlugs = collect($orderItems)->pluck('slug');

        $menus = Menu::with(['menuVariants' => function ($query) {
            $query->orderBy('price', 'asc');
        }])
            ->whereIn('slug', $menuSlugs)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($menus as $menu) {
            $menuVariants = $menu->menuVariants->filter(function ($variant) {
                return count($variant->variant) === 1;
            });

            if (count($menuVariants) > 0) {
                try {
                    $menu->update([
                        'price' => $menuVariants[0]->price,
                        'tax' => $menuVariants[0]->tax,
                        'discount' => $menuVariants[0]->discount,
                    ]);
                } catch (\Exception $e) {
                    Log::critical('This menu failed at fixing variation ----- ' . $menu);
                }

                if ($menuVariants[0]->variant[0]['name'] !== 'default') {
                    MenuVariation::where('menu_id', $menu->id)->delete();

                    $menuVariation = MenuVariation::create([
                        'slug' => StringHelper::generateUniqueSlug(),
                        'name' => $menuVariants[0]->variant[0]['name'],
                        'menu_id' => $menu->id,
                    ]);

                    foreach ($menuVariants as $variant) {
                        MenuVariationValue::create([
                            'slug' => StringHelper::generateUniqueSlug(),
                            'value' => $variant->variant[0]['value'],
                            'price' => $variant->price - $menu->price,
                            'menu_variation_id' => $menuVariation->id,
                        ]);
                    }
                }
            }
        }
    }
}