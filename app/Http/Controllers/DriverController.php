<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
            'slug' => 'required|unique:users',
            'username' => 'required|unique:users',
            'name' => 'required|string',
            'phone_number' => 'required|unique:users',
            'password' => 'required|min:6',
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);

        $driver = User::create($validatedData);

        $driverRoleId = Role::where('name', 'Driver')->first()->id;
        $driver->roles()->attach($driverRoleId);

        return response()->json($driver->refresh()->load('roles'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return User::with('roles')->where('slug', $slug)->firstOrFail();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $driver = User::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'username' => [
                'required',
                Rule::unique('users')->ignore($driver->id),
            ],
            'name' => 'required',
            'phone_number' => [
                'required',
                Rule::unique('users')->ignore($driver->id),
            ],
        ]);

        $driver->update($validatedData);

        $driverRoleId = Role::where('name', 'Driver')->first()->id;
        $driver->roles()->detach();
        $driver->roles()->attach($driverRoleId);

        return response()->json($driver->refresh()->load('roles'), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        $driver = User::where('slug', $slug)->firstOrFail();

        if ($driver->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 406);
        }

        $driver->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
