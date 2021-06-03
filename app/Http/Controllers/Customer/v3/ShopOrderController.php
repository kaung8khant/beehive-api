<?php

namespace App\Http\Controllers\Customer\v3;

use App\Helpers\NotificationHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\ShopOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Promocode;
use App\Models\ShopOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShopOrderController extends Controller
{
    use NotificationHelper, PromocodeHelper, ResponseHelper, StringHelper;

    protected $customer;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer = Auth::guard('customers')->user();
        }
    }

    public function index(Request $request)
    {
        $shopOrder = ShopOrder::where('customer_id', $this->customer->id)
            ->latest()
            ->paginate($request->size)
            ->items();

        return $this->generateShopOrderResponse($shopOrder, 201, 'array');
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();
        $validatedData = OrderHelper::validateOrderV3($request);

        if (gettype($validatedData) == 'string') {
            return $this->generateShopOrderResponse($validatedData, 422, true);
        }

        $validatedData['customer_id'] = $this->customer->id;

        $validatedData = OrderHelper::prepareProductVariant($validatedData);

        if ($validatedData['promo_code']) {
            $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest()->first();
            if (!$promocode) {
                return $this->generateShopOrderResponse('Promocode not found', 422, true);
            }

            $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'shop');
            if (!$validUsage) {
                return $this->generateShopOrderResponse('Invalid promocode usage for shop.', 422, true);
            }

            $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $this->customer, 'shop');
            if (!$validRule) {
                return $this->generateShopOrderResponse('Invalid promocode.', 422, true);
            }

            $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'shop');

            $validatedData['promocode_id'] = $promocode->id;
            $validatedData['promocode'] = $promocode->code;
            $validatedData['promocode_amount'] = $promocodeAmount;
        }

        $order = DB::transaction(function () use ($validatedData) {
            $order = ShopOrder::create($validatedData);
            OrderHelper::createOrderContact($order->id, $validatedData['customer_info'], $validatedData['address']);
            OrderHelper::createShopOrderItem($order->id, $validatedData['order_items']);
            OrderHelper::createOrderStatus($order->id);
            return $order;
        });

        return $this->generateShopOrderResponse($order->refresh(), 201);
    }
}
