<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\StringHelper;
use App\Helpers\ResponseHelper;
use App\Models\Address;
use App\Models\Township;

class AddressController extends Controller
{
    use StringHelper, ResponseHelper;

    protected $customer_id;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer_id = Auth::guard('customers')->user()->id;
        }
    }

    public function index()
    {
        $addresses = Address::with('township')->where('customer_id', $this->customer_id)->paginate(10)->items();
        return $this->generateResponse($addresses, 200);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validator = Validator::make($request->all(), $this->getParamsToValidate(TRUE));
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $validatedData = $validator->validated();
        $validatedData['township_id'] = Township::where('slug', $request->township_slug)->first()->id;
        $validatedData['customer_id'] = $this->customer_id;
        $validatedData['is_primary'] = true;

        $this->setNonPrimary();

        $address = Address::create($validatedData);
        return $this->generateResponse($address->refresh()->load('township'), 201);
    }

    public function show($slug)
    {
        $address = $this->getAddress($slug);
        return $this->generateResponse($address, 200);
    }

    public function update(Request $request, $slug)
    {
        $address = $this->getAddress($slug);

        $validator = Validator::make($request->all(), $this->getParamsToValidate());
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $validatedData = $validator->validated();
        $validatedData['township_id'] = Township::where('slug', $request->township_slug)->first()->id;
        $validatedData['customer_id'] = $this->customer_id;

        $address->update($validatedData);
        return $this->generateResponse($address, 200);
    }

    public function destroy($slug)
    {
        $this->getAddress($slug)->delete();
        return $this->generateResponse('Successfully deleted.', 200, TRUE);
    }

    private function getParamsToValidate($slug = FALSE)
    {
        $params = [
            'label' => 'required',
            'house_number' => 'required',
            'floor' => 'nullable|min:0|max:50',
            'street_name' => 'required',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'township_slug' => 'required|exists:App\Models\Township,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:addresses';
        }

        return $params;
    }

    public function setPrimaryAddress($slug)
    {
        $this->setNonPrimary();

        $address = $this->getAddress($slug);
        $address->is_primary = !$address->is_primary;
        $address->save();

        return $this->generateResponse('Success.', 200, TRUE);
    }

    public function getPrimaryAddress()
    {
        $address = Address::where('customer_id', $this->customer_id)->where('is_primary', 1)->firstOrFail();
        return $this->generateResponse($address, 200);
    }

    public function getAllTownships(Request $request)
    {
        $townships = Township::paginate($request->size)->items();
        return $this->generateResponse($townships, 200);
    }

    private function getAddress($slug)
    {
        return Address::with('township')->where('slug', $slug)->where('customer_id', $this->customer_id)->firstOrFail();
    }

    private function setNonPrimary()
    {
        Address::where('customer_id', $this->customer_id)->update(['is_primary' => 0]);
    }
}
