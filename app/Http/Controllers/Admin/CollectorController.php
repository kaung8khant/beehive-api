<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class CollectorController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('users', 'name', $request->by, $request->order);

        return User::with('roles')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Collector');
            })
            ->where(function ($q) use ($request) {
                $q->where('username', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate(
            [
                'slug' => 'required|unique:users',
                'username' => 'required|unique:users',
                'name' => 'required|string',
                'phone_number' => 'required|phone:MM|unique:users',
                'password' => 'required|min:6',
                'image_slug' => 'nullable|exists:App\Models\File,slug',
            ],
            [
                'phone_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');
        $validatedData['password'] = Hash::make($validatedData['password']);
        $validatedData['created_by'] = Auth::guard('users')->user()->id;

        $collector = User::create($validatedData);

        $collectorRoleId = Role::where('name', 'Collector')->first()->id;
        $collector->roles()->attach($collectorRoleId);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $collector->slug);
        }
        return response()->json($collector->refresh()->load('roles'), 201);
    }

    public function show($slug)
    {
        return User::with('roles')->where('slug', $slug)->firstOrFail();
    }

    public function update(Request $request, $slug)
    {
        $collector = User::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate(
            [
                'username' => [
                    'required',
                    Rule::unique('users')->ignore($collector->id),
                ],
                'name' => 'required',
                'phone_number' => [
                    'required',
                    'phone:MM',
                    Rule::unique('users')->ignore($collector->id),
                ],
                'image_slug' => 'nullable|exists:App\Models\File,slug',
            ],
            [
                'phone_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');

        $collector->update($validatedData);

        $collectorRoleId = Role::where('name', 'Collector')->first()->id;
        $collector->roles()->detach();
        $collector->roles()->attach($collectorRoleId);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $collector->slug);
        }
        return response()->json($collector->refresh()->load('roles'), 200);
    }

    public function destroy($slug)
    {
        $collector = User::where('slug', $slug)->firstOrFail();

        if ($collector->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 406);
        }

        foreach ($collector->images as $image) {
            $this->deleteFile($image->slug);
        }

        $collector->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function toggleEnable(User $user)
    {
        if ($user->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot change your own status.'], 406);
        }

        $user->update(['is_enable' => !$user->is_enable]);
        return response()->json(['message' => 'Success.'], 200);
    }
}
