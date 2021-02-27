<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    use StringHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return User::whereHas('roles', function ($q) {
            $q ->where('name', 'Driver');
        })
        ->paginate(10);
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
            'slug' => 'required|unique:drivers',
            'username' => 'required|unique:drivers',
            'name' => 'required',
            'phone_number' => 'required|unique:drivers',
            'vehicle_number' => 'required|unique:drivers',
        ]);


        $driver = Driver::create($validatedData);

        return response()->json($driver->refresh(), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Driver  $driver
     * @return \Illuminate\Http\Response
     */
    public function show(Driver $driver)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Driver  $driver
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Driver $driver)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Driver  $driver
     * @return \Illuminate\Http\Response
     */
    public function destroy(Driver $driver)
    {
        //
    }
}
