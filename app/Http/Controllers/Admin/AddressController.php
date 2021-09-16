<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    use StringHelper, ResponseHelper;

    public function index($slug)
    {
        $customer = Customer::where('slug', $slug)->firstOrFail();
        $customerId = $customer->id;

        $addresses = Address::where('customer_id', $customerId)->paginate(10)->items();
        return $this->generateResponse($addresses, 200);
    }

    public function store(Request $request, $slug)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validator = Validator::make($request->all(), $this->getParamsToValidate(true));
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $validatedData = $validator->validated();

        $customer = Customer::where('slug', $slug)->firstOrFail();
        $validatedData['customer_id'] = $customer->id;
        $validatedData['is_primary'] = true;

        $this->setNonPrimary($customer->id);

        $address = Address::create($validatedData);
        return $this->generateResponse($address->refresh(), 201);
    }

    public function update(Request $request, Customer $customer, $slug)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validator = Validator::make($request->all(), $this->getParamsToValidate(true));
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $validatedData = $validator->validated();

        $validatedData['customer_id'] = $customer->id;
        $validatedData['is_primary'] = true;

        $this->setNonPrimary($customer->id);
        $address = Address::where('customer_id', $customer->id)->where('slug', $slug)->firstOrFail();
        $address->update($validatedData);
        return response()->json(['message' => 'Success.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'label' => 'required',
            'house_number' => 'nullable',
            'floor' => 'nullable|min:0|max:50',
            'street_name' => 'nullable',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:addresses';
        }

        return $params;
    }

    private function setNonPrimary($id)
    {
        Address::where('customer_id', $id)->update(['is_primary' => 0]);
    }
}
