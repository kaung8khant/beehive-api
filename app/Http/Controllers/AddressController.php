<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Models\Address;
use App\Models\Customer;

class AddressController extends Controller
{
    use StringHelper, ResponseHelper;

    public function index($slug)
    {
        $customer = Customer::where('slug', $slug)->firstOrFail();
        $customerId = $customer->id;

        $addresses = Address::with('township')->where('customer_id', $customerId)->paginate(10)->items();
        return $this->generateResponse($addresses, 200);
    }
}
