<?php

namespace App\Http\Controllers;

use App\Models\StoreVendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;

class StoreVendorController extends Controller
{
    use StringHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($filter)
    {
        return StoreVendor::where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('slug', $filter)->paginate(10);
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

        $storeVendor = StoreVendor::create($request->validate([
            'slug' => 'required|unique:store_vendors',
            'name' => 'required|unique:store_vendors',
            'name_mm'=>'unique:store_vendors',
            'address' => 'required|string:store_vendors',
            'contactNumber'=> "required|string:store_vendors",
            'openingTime'=> "required|timezone:store_vendors",
            'closingTime'=> "required|timezone:store_vendors",
            'enable'=> 'requierd|boolean:store_vendors',
     ]));


        return response()->json($storeVendor, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\StoreVendor  $storeVendor
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        StoreVendor::where('slug', $slug)->firstOrFail();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StoreVendor  $storeVendor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StoreVendor $storeVendor, $slug)
    {
        $storeVendor = StoreVendor::where('slug', $slug)->firstOrFail();

        $storeVendor->update($request->validate([
            'name' => [
                'required',
                Rule::unique('store_vendors')->ignore($storeVendor->id),
            ],
        ]));

        return response()->json($storeVendor, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StoreVendor  $storeVendor
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        StoreVendor::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
