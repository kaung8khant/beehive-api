<?php

namespace App\Http\Controllers\Customer\v3;

use App\Helpers\ResponseHelper;
use App\Helpers\v3\OrderHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use stdClass;

class CreditController extends Controller
{
    use ResponseHelper;

    public function index()
    {
        $customer = Auth::guard('customers')->user();
        $credit = $customer->credit;

        if ($credit) {
            $credit->remaining_amount = OrderHelper::getRemainingCredit($customer->id);
            return $this->generateResponse($credit, 200);
        }

        return $this->generateResponse(new stdClass, 200);
    }
}
