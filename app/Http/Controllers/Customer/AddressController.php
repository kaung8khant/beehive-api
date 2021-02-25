<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\StringHelper;
use App\Models\Address;
use App\Models\Township;

class AddressController extends Controller
{
    use StringHelper;

    protected $customer_id;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer_id = Auth::guard('customers')->user()->id;
        }
    }

    public function index()
    {
        return Address::where('customer_id', $this->customer_id)->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(TRUE));
        $validatedData['township_id'] = Township::where('slug', $request->township_slug)->value('id');
        $validatedData['customer_id'] = $this->customer_id;

        $address = Address::create($validatedData);
        return response()->json($address, 201);
    }

    public function show($slug)
    {
        $address = Address::where('slug', $slug)
            ->where('customer_id', $this->customer_id)
            ->firstOrFail();
        return response()->json($address, 200);
    }

    public function update(Request $request, $slug)
    {
        $address = Address::where('slug', $slug)
            ->where('customer_id', $this->customer_id)
            ->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['township_id'] = Township::where('slug', $request->township_slug)->value('id');
        $validatedData['customer_id'] = $this->customer_id;

        $address->update($validatedData);
        return response()->json($address, 200);
    }

    public function destroy($slug)
    {
        Address::where('slug', $slug)
            ->where('customer_id', $this->customer_id)
            ->firstOrFail()
            ->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = FALSE)
    {
        $params = [
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
        Address::where('customer_id', $this->customer_id)
            ->update(['is_primary' => 0]);

        $address = Address::where('slug', $slug)
            ->where('customer_id', $this->customer_id)
            ->firstOrFail();
        $address->is_primary = !$address->is_primary;
        $address->save();
        return response()->json(['message' => 'Success.'], 200);
    }

    public function getPrimaryAddress()
    {
        $address = Address::where('customer_id', $this->customer_id)
            ->where('is_primary', 1)
            ->firstOrFail();

        return response()->json($address, 200);
    }
}
