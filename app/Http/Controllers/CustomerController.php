<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\Customer;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Customer::paginate(10);
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

        $validatedData = $request->validate([
            'slug' => 'required|unique:customers',
            'username' => 'required|string|min:3|max:100|unique:customers',
            'email' => 'required|email|unique:customers',
            'name' => 'required|max:255',
            'phone_number' => 'required|unique:customers',
            'password' => 'required|string|min:6',
            'gender' => 'required|in:male,female',
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);

        $customer = Customer::create($validatedData);
        return response()->json($customer, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return Customer::where('slug', $slug)->firstOrFail();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $customer = Customer::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'name' => 'required',
            'phone_number' => [
                'required',
                Rule::unique('customers')->ignore($customer->id),
            ],
            'password' => 'required|string|min:6',
            'gender' => 'required|in:male,female',
        ]);

        $customer->update($validatedData);
        return response()->json($customer, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Customer::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     * Toggle the is_enable column for customers table.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function toggleEnable($slug)
    {
        $customer = Customer::where('slug', $slug)->firstOrFail();
        $customer->is_enable = !$customer->is_enable;
        $customer->save();
        return response()->json(['message' => 'Success.'], 200);
    }
}
