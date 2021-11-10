<?php

namespace App\Http\Controllers\Admin\v3;

use App\Events\ShopOrderUpdated;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ServerException;
use App\Helpers\CollectionHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\ShopOrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Helpers\v3\OrderHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Promocode;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderVendor;
use App\Services\MessageService\MessagingService;
use App\Services\PaymentService\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopOrderController extends Controller
{
    use PromocodeHelper, ResponseHelper, StringHelper;

    protected $messageService;
    protected $paymentService;
    protected $resMes;

    public function __construct(MessagingService $messageService, PaymentService $paymentService)
    {
        $this->messageService = $messageService;
        $this->paymentService = $paymentService;
        $this->resMes = config('response-en.shop_order');
    }

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('shop_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        $shopOrders = ShopOrder::exclude(['special_instruction', 'delivery_mode', 'promocode_amount', 'customer_id', 'created_by', 'updated_by'])
            ->with(['contact' => function ($query) {
                $query->exclude(['house_number', 'floor', 'street_name', 'latitude', 'longitude']);
            }])
            ->whereBetween('order_date', array($request->from, $request->to))
            ->where(function ($query) use ($request) {
                $query->where('id', ltrim(ltrim($request->filter, 'BHS'), '0'))
                    ->orWhereHas('contact', function ($q) use ($request) {
                        $q->where('phone_number', $request->filter)
                            ->orWhere('customer_name', 'LIKE', '%' . $request->filter . '%');
                    });
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->get()
            ->makeHidden(['vendors']);

        return $this->generateResponse($shopOrders, 200);
    }

    public function store(Request $request)
    {
        try {
            $request['slug'] = $this->generateUniqueSlug();
            $validatedData = ShopOrderHelper::validateOrderV3($request, true);

            if (gettype($validatedData) == 'string') {
                return $this->generateResponse($validatedData, 422, true);
            }

            try {
                $validatedData = ShopOrderHelper::prepareProductVariants($validatedData);
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
                    $paymentData = $this->paymentService->createTransaction($validatedData, 'shop');
                } catch (ServerException $e) {
                    return $this->generateResponse($e->getMessage(), 500, true);
                }
            }

            $order = $this->shopOrderTransaction($validatedData);

            if ($validatedData['payment_mode'] === 'KPay') {
                $order['prepay_id'] = $paymentData['Response']['prepay_id'];
            } elseif ($validatedData['payment_mode'] === 'CBPay') {
                $order['mer_dqr_code'] = $paymentData['merDqrCode'];
                $order['trans_ref'] = $paymentData['transRef'];
            }

            return $this->generateShopOrderResponse($order, 201);
        } catch (\Exception $e) {
            $url = explode('/', $request->path());

            if ($url[2] === 'admin' || $url[2] === 'vendor') {
                $auth = $url[2] === 'admin' ? 'Admin' : 'Vendor';
                $phoneNumber = Customer::where('slug', $request->customer_slug)->value('phone_number');
                Log::critical($auth . ' shop order ' . $url[1] . ' error: ' . $phoneNumber);
            }

            throw $e;
        }
    }

    public function show(ShopOrder $shopOrder)
    {
        $cache = Cache::get('shopOrder:' . $shopOrder->slug);

        if ($cache) {
            $shopOrder['assign'] = 'pending';
        } else {
            $shopOrder['assign'] = null;
        }

        return $this->generateResponse($shopOrder->load('contact', 'vendors', 'vendors.shop', 'vendors.shopOrderStatuses', 'drivers', 'drivers.status', 'drivers.driver'), 200);
    }

    private function getPromoData($validatedData, $customer)
    {
        $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest()->first();
        if (!$promocode) {
            throw new ForbiddenException('Promocode not found.');
        }

        $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'shop');
        if (!$validUsage) {
            throw new ForbiddenException('Invalid promocode usage for shop.');
        }

        $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer, 'shop');
        if (!$validRule) {
            throw new ForbiddenException('Invalid promocode.');
        }

        $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'shop');

        $validatedData['promocode_id'] = $promocode->id;
        $validatedData['promocode'] = $promocode->code;
        $validatedData['promocode_amount'] = min($validatedData['subTotal'] + $validatedData['tax'], $promocodeAmount);

        return $validatedData;
    }

    private function shopOrderTransaction($validatedData)
    {
        $order = DB::transaction(function () use ($validatedData) {
            $order = ShopOrder::create($validatedData);
            ShopOrderHelper::createOrderContact($order->id, $validatedData['customer_info'], $validatedData['address']);
            ShopOrderHelper::createShopOrderItem($order->id, $validatedData['order_items']);
            ShopOrderHelper::createOrderStatus($order->id);
            return $order->refresh()->load('contact');
        });

        $phoneNumber = Customer::where('id', $order->customer_id)->value('phone_number');
        ShopOrderHelper::notifySystem($order, $validatedData['order_items'], $phoneNumber, $this->messageService);

        return $order;
    }

    public function changeStatus(Request $request, ShopOrder $shopOrder)
    {
        if ($shopOrder->order_status === 'delivered' || $shopOrder->order_status === 'cancelled') {
            $superUser = Auth::guard('users')->user()->roles->contains('name', 'SuperAdmin');
            if (!$superUser) {
                return $this->generateResponse(sprintf($this->resMes['order_sts_err'], $shopOrder->order_status), 406, true);
            }
        }

        if ($shopOrder->payment_mode !== 'COD' && $shopOrder->payment_status !== 'success') {
            if ($request->status !== 'cancelled') {
                return $this->generateResponse($this->resMes['payment_err'], 406, true);
            }
        }

        ShopOrderHelper::createOrderStatus($shopOrder->id, $request->status);

        $orderItems = $shopOrder->vendors->map(function ($vendor) {
            return $vendor->items;
        })->collapse()->values()->map(function ($item) {
            unset($item->shop);
            $item->slug = Product::where('id', $item->id)->value('slug');
            return $item;
        })->toArray();

        $shopOrder['order_status'] = $request->status;
        ShopOrderHelper::sendPushNotifications($shopOrder, $orderItems, 'Order Number:' . $shopOrder->invoice_id . ', is now ' . $request->status);

        if ($request->status === 'cancelled') {
            $message = 'Your order has successfully been ' . $request->status . '.';
            $smsData = SmsHelper::prepareSmsData($message);
            $uniqueKey = StringHelper::generateUniqueSlug();
            $phoneNumber = Customer::where('id', $shopOrder->customer_id)->first()->phone_number;

            SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData, $this->messageService);
        }

        if ($request->status === 'pickUp') {
            event(new ShopOrderUpdated($shopOrder));
        }

        return $this->generateResponse(sprintf($this->resMes['order_sts_succ'], $request->status), 200, true);
    }

    public function getVendorOrders(Request $request, Shop $shop)
    {
        $vendorShopId = Auth::guard('vendors')->user()->shop_id;
        if ($vendorShopId !== $shop->id) {
            abort(404);
        }

        $sorting = CollectionHelper::getSorting('shop_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        $vendorOrders = ShopOrderVendor::where('shop_id', $shop->id)
            ->where(function ($query) use ($request) {
                $query->whereHas('shopOrder', function ($q) use ($request) {
                    $q
                        ->where('id', ltrim(ltrim($request->filter, 'BHS'), '0'));
                })
                    ->orWhereHas('shopOrder.contact', function ($q) use ($request) {
                        $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                            ->orWhere('phone_number', $request->filter);
                    });
            })
            ->where(function ($query) use ($request) {
                $query->whereHas('shopOrder', function ($q) use ($request) {
                    $q->whereBetween('order_date', array($request->from, $request->to));
                });
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->get();

        $result = $vendorOrders->map(function ($order) {
            $shopOrder = ShopOrder::with('contact')->find($order->shop_order_id)->toArray();
            unset($shopOrder['vendors']);

            $order->shop_order = $shopOrder;
            $order = $order->toArray();

            unset($order['items']);
            return $order;
        });

        return $this->generateResponse($result, 200);
    }

    public function cancelOrderItem(ShopOrder $shopOrder, ShopOrderItem $shopOrderItem)
    {
        $shopOrderItem->delete();
        $shopOrder = ShopOrder::where('slug', $shopOrder->slug)->first();
        $vendors = $shopOrder->vendors;
        $commission = 0;
        foreach ($vendors as $vendor) {
            foreach ($vendor->items as $item) {
                $commission += $item->commission;
            }
        }
        $shopOrder->update(['commission' => $commission]);
        return response()->json(['message' => 'Successfully cancelled.'], 200);
    }

    public function updatePayment(Request $request, ShopOrder $shopOrder)
    {
        $shopOrder->update($request->validate([
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'payment_status' => 'required|in:success,failed,pending',
        ]));

        return $this->generateResponse('The order payment has successfully been updated.', 200, true);
    }
}
