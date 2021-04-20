<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Township;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

        $validator = Validator::make($request->all(), $this->getParamsToValidate(true));
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $validatedData = $validator->validated();
        $validatedData['customer_id'] = $this->customer_id;
        $validatedData['is_primary'] = true;

        if ($validatedData['township_slug']) {
            $validatedData['township_id'] = Township::where('slug', $validatedData['township_slug'])->first()->id;
        }

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
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $validatedData = $validator->validated();
        $validatedData['customer_id'] = $this->customer_id;
        $validatedData['township_id'] = null;

        if ($validatedData['township_slug']) {
            $validatedData['township_id'] = Township::where('slug', $validatedData['township_slug'])->first()->id;
        }

        $address->update($validatedData);
        return $this->generateResponse($address->refresh(), 200);
    }

    public function destroy($slug)
    {
        $this->getAddress($slug)->delete();
        return $this->generateResponse('Successfully deleted.', 200, true);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'label' => 'required',
            'house_number' => 'required',
            'floor' => 'nullable|min:0|max:50',
            'street_name' => 'required',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'township_slug' => 'nullable|exists:App\Models\Township,slug',
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

        return $this->generateResponse('Success.', 200, true);
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
