<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

trait AuthHelper
{
    public static function getCustomerId()
    {
        if ($customer = Auth::guard('customers')->user()) {
            $customerId = $customer->id;
        } else {
            $customerId = null;
        }

        return $customerId;
    }
}
