<?php

namespace App\Http\Controllers\Customer\v3;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    use ResponseHelper;

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
