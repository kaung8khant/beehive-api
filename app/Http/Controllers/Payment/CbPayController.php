<?php

namespace App\Http\Controllers\Payment;

use App\Exceptions\ServerException;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
use App\Services\PaymentService\CbPayService;

class CbPayController extends Controller
{
    use ResponseHelper;

    public function checkTransaction($orderType, $slug)
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
            'transRef' => $order->payment_reference,
        ];

        try {
            $cbService = new CbPayService();
            $transactionData = $cbService->checkTransaction($transactionRequest);

            $transStatus = $this->updatePaymentStatus($order, $transactionData['transStatus']);
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

        $requestData = [
            'slug' => $slug,
            'totalAmount' => $order->total_amount,
        ];

        try {
            $cbService = new CbPayService();
            $paymentData = $cbService->createTransaction($requestData, $orderType);

            $order->update([
                'payment_status' => 'pending',
                'payment_reference' => $paymentData['transRef'],
            ]);

            return $this->generateResponse(['mer_dqr_code' => $paymentData['merDqrCode']], 200);
        } catch (ServerException $e) {
            return $this->generateResponse($e->getMessage(), 500, true);
        }
    }

    private function updatePaymentStatus($order, $transStatus)
    {
        $paymentStatus = 'pending';

        switch ($transStatus) {
            case 'P':
                $paymentStatus = 'pending';
                break;
            case 'S':
                $paymentStatus = 'success';
                break;
            case 'E':
                $paymentStatus = 'expired';
                break;
            case 'C':
                $paymentStatus = 'cancelled';
                break;
            case 'L':
                $paymentStatus = 'overLimit';
                break;
        }

        $order->update(['payment_status' => $paymentStatus]);
        return config('payment.cb_pay.trans_status')[$transStatus];
    }
}
