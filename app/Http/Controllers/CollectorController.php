<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CollectorController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        return User::with('roles')
        ->whereHas('roles', function ($q) {
            $q ->where('name', 'Collector');
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

        $collector = User::create($validatedData);

        $collectorRoleId = Role::where('name', 'Collector')->first()->id;
        $collector->roles()->attach($collectorRoleId);

        return response()->json($collector->refresh()->load('roles'), 201);
    }


    public function show($slug)
    {
        return User::with('roles')->where('slug', $slug)->firstOrFail();
    }

    public function update(Request $request, $slug)
    {
        $collector = User::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'username' => [
                'required',
                Rule::unique('users')->ignore($collector->id),
            ],
            'name' => 'required',
            'phone_number' => [
                'required',
                Rule::unique('users')->ignore($collector->id),
            ],
        ]);

        $collector->update($validatedData);

        $collectorRoleId = Role::where('name', 'Collector')->first()->id;
        $collector->roles()->detach();
        $collector->roles()->attach($collectorRoleId);

        return response()->json($collector->refresh()->load('roles'), 200);
    }

    public function destroy($slug)
    {
        $collector = User::where('slug', $slug)->firstOrFail();

        if ($collector->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 406);
        }

        $collector->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
