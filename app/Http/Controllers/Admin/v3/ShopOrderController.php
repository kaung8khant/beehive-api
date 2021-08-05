<?php

namespace App\Http\Controllers\Admin\v3;

use App\Exceptions\ForbiddenException;
use App\Helpers\CollectionHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\ShopOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopOrderController extends Controller
{
    use PromocodeHelper, ResponseHelper, StringHelper;

    protected $messageService;

    public function __construct(MessagingService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('shop_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        if ($request->filter) {
            $shopOrders = ShopOrder::with('contact')
                ->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->orWhereHas('contact', function ($query) use ($request) {
                    $query->where('phone_number', $request->filter)->orWhere('customer_name', 'LIKE', '%' . $request->filter . '%');
                })
                ->orWhere('id', ltrim(ltrim($request->filter, 'BHS'), '0'))
                ->get()
                ->map(function ($shopOrder) {
                    return $shopOrder->makeHidden('vendors');
                });
        } else {
            $shopOrders = ShopOrder::with('contact')
                ->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->whereBetween('order_date', array($request->from, $request->to))
                ->get()
                ->map(function ($shopOrder) {
                    return $shopOrder->makeHidden('vendors');
                });
        }
        return $this->generateResponse($shopOrders, 200);
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
                $validatedData = OrderHelper::prepareProductVariants($validatedData);
            } catch (ForbiddenException $e) {
                return $this->generateResponse($e->getMessage(), 403, true);
            }

            $customer = Customer::where('slug', $validatedData['customer_slug'])->first();

            if ($validatedData['promo_code']) {
                $validatedData = $this->getPromoData($validatedData, $customer);
            }

            $order = $this->shopOrderTransaction($validatedData);

            return $this->generateShopOrderResponse($order, 201);
        } catch (\Exception $e) {
            $url = explode('/', $request->path());
            $auth = $url[2] === 'admin' ? 'Admin' : 'Vendor';
            $phoneNumber = Customer::where('slug', $request->customer_slug)->value('phone_number');

            Log::critical($auth . ' shop order ' . $url[1] . ' error: ' . $phoneNumber);
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

        return $this->generateResponse($shopOrder->load('contact', 'vendors', 'vendors.shop', 'vendors.shopOrderStatuses', 'drivers', 'drivers.status'), 200);
    }

    private function getPromoData($validatedData, $customer)
    {
        $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest()->first();
        if (!$promocode) {
            return $this->generateResponse('Promocode not found', 422, true);
        }

        $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'shop');
        if (!$validUsage) {
            return $this->generateResponse('Invalid promocode usage for shop.', 422, true);
        }

        $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer, 'shop');
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

        $phoneNumber = Customer::where('id', $order->customer_id)->value('phone_number');
        OrderHelper::notifySystem($order, $validatedData['order_items'], $phoneNumber, $this->messageService);

        return $order;
    }

    public function changeStatus(Request $request, ShopOrder $shopOrder)
    {
        if ($shopOrder->order_status === 'delivered' || $shopOrder->order_status === 'cancelled') {
            $superUser = Auth::guard('users')->user()->roles->contains('name', 'SuperAdmin');
            if (!$superUser) {
                return $this->generateResponse('The order has already been ' . $shopOrder->order_status . '.', 406, true);
            }
        }

        OrderHelper::createOrderStatus($shopOrder->id, $request->status);

        $orderItems = $shopOrder->vendors->map(function ($vendor) {
            return $vendor->items;
        })->collapse()->values()->map(function ($item) {
            unset($item->shop);
            $item->slug = Product::where('id', $item->id)->value('slug');
            return $item;
        })->toArray();

        $shopOrder['order_status'] = $request->status;
        OrderHelper::sendPushNotifications($shopOrder, $orderItems, 'Order Number:' . $shopOrder->invoice_id . ', is now ' . $request->status);

        if ($request->status === 'cancelled') {
            $message = 'Your order has successfully been ' . $request->status . '.';
            $smsData = SmsHelper::prepareSmsData($message);
            $uniqueKey = StringHelper::generateUniqueSlug();
            $phoneNumber = Customer::where('id', $shopOrder->customer_id)->first()->phone_number;

            SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData, $this->messageService);
        }

        return $this->generateResponse('The order has successfully been ' . $request->status . '.', 200, true);
    }

    public function getVendorOrders(Request $request, Shop $shop)
    {
        $vendorShopId = Auth::guard('vendors')->user()->shop_id;
        if ($vendorShopId !== $shop->id) {
            abort(404);
        }

        $sorting = CollectionHelper::getSorting('shop_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        if ($request->filter) {
            $vendorOrders = ShopOrderVendor::where('shop_id', $shop->id)
                ->where(function ($query) use ($request) {
                    $query->whereHas('shopOrder', function ($q) use ($request) {
                        $q->orWhere('id', ltrim(ltrim($request->filter, 'BHS'), '0'));
                    })
                        ->orWhereHas('shopOrder.contact', function ($q) use ($request) {
                            $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                                ->orWhere('phone_number', $request->filter);
                        });
                })
                ->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->get();
        } else {
            $vendorOrders = ShopOrderVendor::where('shop_id', $shop->id)
                ->whereHas('shopOrder', function ($query) use ($request) {
                    $query->whereBetween('order_date', array($request->from, $request->to));
                })
                ->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->get();
        }

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
        $promocode = Promocode::where('code', $shopOrder->promocode)->first();

        $vendors = $shopOrder->vendors;
        $subTotal = 0;
        $commission = 0;

        foreach ($vendors as $vendor) {
            foreach ($vendor->items as $item) {
                $commission += $item->commission;

                $subTotal += ($item->amount - $item->discount) * $item->quantity;
            }
        }

        if ($promocode) {
            if ($promocode->type === 'fix') {
                $shopOrder->update(['promocode_amount' => $promocode->amount, 'commission' => $commission]);
            } else {
                $shopOrder->update(['promocode_amount' => $subTotal * $promocode->amount * 0.01, 'commission' => $commission]);
            }
        }

        return response()->json(['message' => 'Successfully cancelled.'], 200);
    }
}
