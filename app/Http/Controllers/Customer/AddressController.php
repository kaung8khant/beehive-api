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

    /**
     * Create a new AddressController instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer_id = Auth::guard('customers')->user()->id;
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Address::where('customer_id', $this->customer_id)->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(TRUE));
        $validatedData['township_id'] = Township::where('slug', $request->township_id)->value('id');
        $validatedData['customer_id'] = $this->customer_id;

        $address = Address::create($validatedData);
        return response()->json($address, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Address  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $address = Address::where('slug', $slug)
            ->where('customer_id', $this->customer_id)
            ->firstOrFail();
        return response()->json($address, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Address  $slug
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $address = Address::where('slug', $slug)
            ->where('customer_id', $this->customer_id)
            ->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['township_id'] = Township::where('slug', $request->township_id)->value('id');
        $validatedData['customer_id'] = $this->customer_id;

        $address->update($validatedData);
        return response()->json($address, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Address  $slug
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Address::where('slug', $slug)
            ->where('customer_id', $this->customer_id)
            ->firstOrFail()
            ->delete();
        return response()->json(['message' => 'Successfully deleted'], 200);
    }

    /**
     * Validate the address.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    private function getParamsToValidate($slug = FALSE)
    {
        $params = [
            'house_number' => 'required',
            'floor' => 'nullable|min:0|max:50',
            'street_name' => 'required',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'township_id' => 'required|exists:App\Models\Township,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:addresses';
        }

        return $params;
    }

    /**
     * Toggle the is_primary column for addresses table.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Get the primary address for a customer.
     *
     * @param  \App\Models\Address  $slug
     * @return \Illuminate\Http\Response
     */
    public function getPrimaryAddress()
    {
        $address = Address::where('customer_id', $this->customer_id)
            ->where('is_primary', 1)
            ->firstOrFail();

        return response()->json($address, 200);
    }
}
