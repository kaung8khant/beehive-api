<?php

namespace App\Http\Controllers\Payment;

use App\Exceptions\ServerException;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
use App\Services\PaymentService\CbPayService;
use Illuminate\Http\Request;

class CbPayController extends Controller
{
    use ResponseHelper;

    public function checkTransaction(Request $request, $orderType, $slug)
    {
        if ($orderType === 'shop') {
            $order = ShopOrder::where('slug', $slug)->firstOrFail();
        } elseif ($orderType === 'restaurant') {
            $order = RestaurantOrder::where('slug', $slug)->firstOrFail();
        } else {
            return $this->generateResponse('order type must be shop or restaurant', 422, true);
        }

        $transactionRequest = [
            'merId' => config('payment.cb_pay.merch_id'),
            'transRef' => $request->trans_ref,
        ];

        try {
            $cbService = new CbPayService();
            $transactionData = $cbService->checkTransaction($transactionRequest);

            $transStatus = config('payment.cb_pay.trans_status')[$transactionData['transStatus']];

            if ($transStatus === 'S') {
                $order->update(['payment_status' => 'success']);
            } else if ($transStatus === 'C') {
                $order->update(['payment_status' => 'cancelled']);
            }

            return $this->generateResponse($transStatus, 200, true);
        } catch (ServerException $e) {
            return $this->generateResponse($e->getMessage(), 500, true);
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
            $cbService = new CbPayService();
            $paymentData = $cbService->createTransaction($requestData, $orderType);

            $response = [
                'mer_dqr_code' => $paymentData['merDqrCode'],
                'trans_ref' => $paymentData['transRef']
            ];

            return $this->generateResponse($response, 200);
        } catch (ServerException $e) {
            return $this->generateResponse($e->getMessage(), 500, true);
        }
    }
}
