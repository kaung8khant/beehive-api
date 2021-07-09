<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\DriverAttendance;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class DriverController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('users', 'name', $request->by, $request->order);

        return User::with('roles')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Driver');
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

        $checkPhone = User::where('phone_number', $validatedData['phone_number'])->first();

        if ($checkPhone) {
            return [
                'message' => 'The given data was invalid.',
                'errors' => [
                    'phone_number' => [
                        'The phone number has already been taken.',
                    ],
                ],
            ];
        }

        $validatedData['password'] = Hash::make($validatedData['password']);
        $validatedData['created_by'] = Auth::guard('users')->user()->id;

        $driver = User::create($validatedData);

        $driverRoleId = Role::where('name', 'Driver')->first()->id;
        $driver->roles()->attach($driverRoleId);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $driver->slug);
        }
        return response()->json($driver->refresh()->load('roles'), 201);
    }

    public function show($slug)
    {
        return User::with('roles')->where('slug', $slug)->firstOrFail();
    }

    public function update(Request $request, $slug)
    {
        $driver = User::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate(
            [
                'username' => [
                    'required',
                    Rule::unique('users')->ignore($driver->id),
                ],
                'name' => 'required',
                'phone_number' => [
                    'required',
                    'phone:MM',
                    Rule::unique('users')->ignore($driver->id),
                ],
                'image_slug' => 'nullable|exists:App\Models\File,slug',
            ],
            [
                'phone_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');
        $checkPhone = User::where('phone_number', $validatedData['phone_number'])->where('id', '<>', $driver->id)->first();

        if ($checkPhone) {
            return [
                'message' => 'The given data was invalid.',
                'errors' => [
                    'phone_number' => [
                        'The phone number has already been taken.',
                    ],
                ],
            ];
        }

        $driver->update($validatedData);

        $driverRoleId = Role::where('name', 'Driver')->first()->id;
        $driver->roles()->detach();
        $driver->roles()->attach($driverRoleId);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $driver->slug);
        }
        return response()->json($driver->refresh()->load('roles'), 200);
    }

    public function destroy($slug)
    {
        $driver = User::where('slug', $slug)->firstOrFail();

        if ($driver->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 406);
        }

        foreach ($driver->images as $image) {
            $this->deleteFile($image->slug);
        }

        $driver->delete();
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

    public function attendance(Request $request)
    {
        $rules = [
            'type' => 'in:check-in,check-out',
        ];

        $validator = Validator::make($request->all(), $rules);

        $request['user_id'] = Auth::guard('users')->user()->id;

        $data = DriverAttendance::where('user_id', $request->user_id)->where('type', $request->type)->whereDate('created_at', Carbon::now()->format('Y-m-d'))->get();

        if (count($data) == 0) {
            if ($validator->fails()) {
                return $this->generateResponse($validator->errors()->first(), 422, true);
            }
            DriverAttendance::create(['user_id' => $request->user_id, 'type' => $request->type]);

            return response()->json(['message' => 'Success.'], 200);
        }
        return response()->json(['message' => 'Already ' . $request->type], 409);
    }

    public function getCheckin()
    {
        $user_id = Auth::guard('users')->user()->id;
        $data = DriverAttendance::where('user_id', $user_id)->get();

        return response()->json(['data' => $data], 200);
    }

    public function profile()
    {
        $profile = Auth::guard('users')->user();
        return response()->json(Auth::guard('users')->user());
    }
}
