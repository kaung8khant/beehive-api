<?php

namespace App\Http\Controllers\Payment;

use App\Exceptions\ServerException;
use App\Helpers\KbzPayHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
use App\Services\PaymentService\KbzPayService;
use Illuminate\Http\Request;

class KbzPayController extends Controller
{
    use ResponseHelper;

    public function notify(Request $request)
    {
        $requestData = $request->all();
        unset($requestData['Request']['sign']);
        unset($requestData['Request']['sign_type']);

        $sign = KbzPayHelper::signature(KbzPayHelper::joinKeyValue($requestData['Request']));

        if ($sign === $request->Request['sign'] && $requestData['Request']['trade_status'] === 'PAY_SUCCESS') {
            $merchOrderId = explode('-', $requestData['Request']['merch_order_id']);
            $orderType = $merchOrderId[0];
            $orderSlug = $merchOrderId[1];

            $data = [
                'payment_status' => 'success',
                'payment_reference' => $requestData['Request']['mm_order_id'],
            ];

            if ($orderType === 'SHOP') {
                ShopOrder::where('slug', $orderSlug)->update($data);
            } elseif ($orderType === 'RESTAURANT') {
                RestaurantOrder::where('slug', $orderSlug)->update($data);
            }

            return 'success';
        }
    }

    public function pay($orderType, $slug)
    {
        if ($orderType === 'shop') {
            $order = ShopOrder::where('slug', $slug)->firstOrFail();
        } elseif ($orderType === 'restaurant') {
            $order = RestaurantOrder::where('slug', $slug)->firstOrFail();
        } else {
            return $this->generateResponse('order type must be shop or restaurant', 422, true);
        }

        $requestData['slug'] = $slug;
        $requestData['totalAmount'] = $order->total_amount;

        try {
            $kbzService = new KbzPayService();
            $paymentData = $kbzService->createTransaction($requestData, $orderType);

            return $this->generateResponse(['prepay_id' => $paymentData['Response']['prepay_id']], 200);
        } catch (ServerException $e) {
            return $this->generateResponse($e->getMessage(), 500, true);
        }
    }
}
