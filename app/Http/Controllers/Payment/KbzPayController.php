<?php

namespace App\Http\Controllers\Payment;

use App\Helpers\KbzPayHelper;
use App\Http\Controllers\Controller;
use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
use Illuminate\Http\Request;

class KbzPayController extends Controller
{
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

            if ($orderType === 'SHOP') {
                ShopOrder::where('slug', $orderSlug)->update(['payment_status' => 'success']);
            } elseif ($orderType === 'RESTAURANT') {
                RestaurantOrder::where('slug', $orderSlug)->update(['payment_status' => 'success']);
            }

            return 'success';
        }
    }
}
