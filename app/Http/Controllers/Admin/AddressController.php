<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Township;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        if ($validatedData['township_slug']) {
            $validatedData['township_id'] = Township::where('slug', $validatedData['township_slug'])->first()->id;
        }

        $this->setNonPrimary($customer->id);

        $address = Address::create($validatedData);
        return $this->generateResponse($address->refresh()->load('township'), 201);
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
            'township_slug' => 'nullable|exists:App\Models\Township,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:addresses';
        }

        return $params;
    }

    public function getAllTownships(Request $request)
    {
        $townships = Township::paginate($request->size)->items();
        return $this->generateResponse($townships, 200);
    }

    private function setNonPrimary($id)
    {
        Address::where('customer_id', $id)->update(['is_primary' => 0]);
    }
}
