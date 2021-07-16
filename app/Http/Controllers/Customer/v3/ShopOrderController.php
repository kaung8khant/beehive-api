<?php

namespace App\Http\Controllers\Customer\v3;

use App\Exceptions\ForbiddenException;
use App\Exceptions\ServerException;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\ShopOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\Promocode;
use App\Models\ShopOrder;
use App\Services\MessageService\MessagingService;
use App\Services\PaymentService\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopOrderController extends Controller
{
    use PromocodeHelper, ResponseHelper, StringHelper;

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
        $shopOrder = ShopOrder::where('customer_id', $this->customer->id)
            ->orderBy('id', 'desc')
            ->paginate($request->size)
            ->items();

        return $this->generateShopOrderResponse($shopOrder, 200, 'array');
    }

    public function store(Request $request)
    {
        return $this->generateResponse('We are closed temporarily starting from 17.July.2021 due to the current coronavirus outbreak in Myanmar.
        We are sorry for any inconvenience caused and we will see you again soon.', 503, true);

        try {
            $request['slug'] = $this->generateUniqueSlug();
            $validatedData = OrderHelper::validateOrderV3($request);

            if (gettype($validatedData) == 'string') {
                return $this->generateResponse($validatedData, 422, true);
            }

            try {
                $validatedData = OrderHelper::prepareProductVariants($validatedData);
            } catch (ForbiddenException $e) {
                return $this->generateResponse($e->getMessage(), 403, true);
            }

            if ($validatedData['promo_code']) {
                $validatedData = $this->getPromoData($validatedData);
            }

            $paymentData = [];
            if ($validatedData['payment_mode'] !== 'COD') {
                try {
                    $paymentData = $this->paymentService->createTransaction($validatedData, 'shop');
                } catch (ServerException $e) {
                    return $this->generateResponse($e->getMessage(), 500, true);
                }
            }

            $order = $this->shopOrderTransaction($validatedData);

            if ($validatedData['payment_mode'] === 'KPay') {
                $order['prepay_id'] = $paymentData['Response']['prepay_id'];
            }

            return $this->generateShopOrderResponse($order, 201);
        } catch (\Exception $e) {
            Log::critical('Customer shop order v3 error: ' . Auth::guard('customers')->user()->phone_number);
            throw $e;
        }
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
        return $this->generateResponse('You cannot cancel order at the moment. Please contact support.', 403, true);

        $shopOrder = ShopOrder::with('vendors')
            ->where('customer_id', $this->customer->id)
            ->where('slug', $slug)
            ->firstOrFail();

        if ($shopOrder->order_status === 'delivered' || $shopOrder->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $shopOrder->order_status . '.', 406, true);
        }

        $message = 'Your order has been cancelled.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendSms::dispatch($uniqueKey, [$this->customer->phone_number], $message, 'order', $smsData, $this->messageService);
        OrderHelper::createOrderStatus($shopOrder->id, 'cancelled');

        return $this->generateResponse($message, 200, true);
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

        OrderHelper::notifySystem($order, $validatedData['order_items'], $this->customer->phone_number, $this->messageService);

        return $order;
    }
}
