<?php

namespace App\Services\PaymentService;

abstract class PaymentService
{
    abstract public function createTransaction($validatedData, $orderType);
}
