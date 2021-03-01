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

    public function index(Request $request)
    {
        return User::with('roles')
        ->whereHas('roles', function ($q) {
            $q ->where('name', 'Driver');
        })
        ->where(function ($q) use ($request) {
            $q->where('username', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter);
        })
        ->paginate(10);
    }

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

    public function show($slug)
    {
        return User::with('roles')->where('slug', $slug)->firstOrFail();
    }

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

    public function destroy($slug)
    {
        $driver = User::where('slug', $slug)->firstOrFail();

        if ($driver->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 406);
        }

        $driver->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function toggleEnable($slug)
    {
        $driver = User::where('slug', $slug)->firstOrFail();

        if ($driver->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot change your own status.'], 406);
        }

        $driver->is_enable = !$driver->is_enable;
        $driver->save();
        return response()->json(['message' => 'Success.'], 200);
    }
}
