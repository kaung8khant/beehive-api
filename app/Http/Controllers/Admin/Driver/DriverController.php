<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Driver\DriverCreateRequest;
use App\Repositories\Driver\DriverUpdateRequest;
use App\Models\DriverAttendance;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderDriver;
use App\Models\RestaurantOrderDriverStatus;
use App\Models\Role;
use App\Models\ShopOrderDriver;
use App\Models\User;
use App\Repositories\Driver\DriverRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;

class DriverController extends Controller
{
    use ResponseHelper, FileHelper, StringHelper;

    public function __construct(DriverRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('users', 'name', $request->by, $request->order);

        return $this->repository->getAllDriver($sorting);
    }

    public function store(DriverCreateRequest $request)
    {
        $phoneNumber = PhoneNumber::make($request->phone_number, 'MM');
        $driver = $this->repository->getDriverWithPhone($phoneNumber);

        $driverRoleId = Role::where('name', 'Driver')->value('id');
        if ($driver) {
            if ($driver->roles->contains('name', 'Driver')) {
                return $this->generateResponse('The phone number has already been taken.', 422, true);
            } else {
                $driver->roles()->attach($driverRoleId);
            }
        } else {
            $validatedData = $request->validated();


            $validatedData['phone_number'] = $phoneNumber;
            $validatedData['password'] = Hash::make($validatedData['password']);
            $driver = $this->repository->create($validatedData);
            $driver->roles()->attach($driverRoleId);
        }

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $driver->slug);
        }

        return response()->json($driver->refresh()->load('roles'), 201);
    }

    public function show($slug)
    {
        return User::with('roles')->where('slug', $slug)->firstOrFail();
    }

    public function update(DriverUpdateRequest $request, User $driver)
    {
        $validatedData = $request->validate();

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');

        $checkPhone = $this->repository->getDriverWithPhone($validatedData['phone_number'], $driver->id);

        if ($checkPhone) {
            return $this->generateResponse('The phone number has already been taken.', 422, true);
        }

        $this->repository->update($driver->id, $validatedData);

        return response()->json($driver->refresh()->load('roles'), 200);
    }

    public function destroy($slug)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

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
