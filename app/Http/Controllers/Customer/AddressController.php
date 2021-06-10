<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\City;
use App\Models\Township;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    use StringHelper, ResponseHelper;

    protected $customerId;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customerId = Auth::guard('customers')->user()->id;
        }
    }

    public function index()
    {
        $addresses = Address::with('township')->where('customer_id', $this->customerId)->paginate(10)->items();
        return $this->generateResponse($addresses, 200);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validator = $this->validateAddress($request, true);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $validatedData = $validator->validated();
        $validatedData['customer_id'] = $this->customerId;
        $validatedData['is_primary'] = true;

        if ($validatedData['township_slug']) {
            $validatedData['township_id'] = Township::where('slug', $validatedData['township_slug'])->first()->id;
        }

        $this->setNonPrimary();

        $address = Address::create($validatedData);
        return $this->generateResponse($address->refresh()->load('township'), 201);
    }

    public function show(Address $address)
    {
        return $this->generateResponse($address->load('township'), 200);
    }

    public function update(Request $request, Address $address)
    {
        $validator = $this->validateAddress($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $validatedData = $validator->validated();
        $validatedData['customer_id'] = $this->customerId;
        $validatedData['township_id'] = null;

        if ($validatedData['township_slug']) {
            $validatedData['township_id'] = Township::where('slug', $validatedData['township_slug'])->first()->id;
        }

        $address->update($validatedData);
        return $this->generateResponse($address->load('township'), 200);
    }

    public function destroy(Address $address)
    {
        $address->delete();
        return $this->generateResponse('Successfully deleted.', 200, true);
    }

    private function validateAddress($request, $slug = false)
    {
        $params = [
            'label' => 'required',
            'house_number' => 'required',
            'floor' => 'nullable|integer|min:0|max:50',
            'street_name' => 'required',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'township_slug' => 'nullable|exists:App\Models\Township,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:addresses';
        }

        return Validator::make($request->all(), $params);
    }

    public function setPrimaryAddress(Address $address)
    {
        $this->setNonPrimary();
        $address->is_primary = !$address->is_primary;
        $address->save();

        return $this->generateResponse('Success.', 200, true);
    }

    public function getPrimaryAddress()
    {
        $address = Address::where('customer_id', $this->customerId)->where('is_primary', 1)->firstOrFail();
        return $this->generateResponse($address, 200);
    }

    public function getAllCities(Request $request)
    {
        $cities = City::where('name', 'LIKE', '%' . $request->filter . '%')->get();
        return $this->generateResponse($cities, 200);
    }

    public function getTownshipsByCity(Request $request, City $city)
    {
        $townships = Township::where('city_id', $city->id)
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->get();

        return $this->generateResponse($townships, 200);
    }

    public function getAllTownships()
    {
        $townships = Township::all();
        return $this->generateResponse($townships, 200);
    }

    private function setNonPrimary()
    {
        Address::where('customer_id', $this->customerId)->update(['is_primary' => 0]);
    }

    public function getNearestAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $address = Address::with('township')
            ->selectRaw('label, house_number, street_name, latitude, longitude, is_primary, township_id,
        ( 6371 * acos( cos(radians(?)) *
            cos(radians(latitude)) * cos(radians(longitude) - radians(?))
            + sin(radians(?)) * sin(radians(latitude)) )
        ) AS distance', [$request->lat, $request->lng, $request->lat])
            ->having('distance', '<', 1)
            ->orderBy('distance', 'asc')
            ->where('customer_id', Auth::guard('customers')->user()->id)
            ->first();

        return $this->generateResponse($address, 200);
    }
}
