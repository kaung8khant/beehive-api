<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    use StringHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Address::paginate(10);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['slug']=$this->generateUniqueSlug();

        $address=Address::create($request->validate(
            [
                'slug' => 'required|unique:addresses',
                'house_number' => 'required',
                'floor' => 'nullable|min:0|max:50',
                'street_name' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'is_primary' => 'required|boolean:addresses',
                'township_id' => 'required|exists:App\Models\Township,id'
            ]
        ));
        return response()->json($address, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(Address::where('slug', $slug)->firstOrFail(),200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $address = Address::where('slug', $slug)->firstOrFail();

        $address->update($request->validate([
            'house_number' => 'required',
            'floor' => 'nullable|min:0|max:50',
            'street_name' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'is_primary' => 'required|boolean:addresses',
            'township_id' => 'required|exists:App\Models\Township,id'
        ]));
        return response()->json($address, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Address::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message'=>'successfully deleted'], 200);
    }
}