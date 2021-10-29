<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\Customer;
use App\Models\RestaurantBranch;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class UserController extends Controller
{
    use FileHelper, StringHelper, ResponseHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('users', 'name', $request->by, $request->order);

        return User::with('roles')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Admin')->orWhere('name', 'SuperAdmin');
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

    public function getShopUsers(Request $request)
    {
        $sorting = CollectionHelper::getSorting('users', 'name', $request->by, $request->order);

        return User::with('roles')->with('shop')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Shop');
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

    public function getRestaurantUsers(Request $request)
    {
        $sorting = CollectionHelper::getSorting('users', 'name', $request->by, $request->order);

        return User::with('roles')->with('restaurantBranch')->with('restaurantBranch.restaurant')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Restaurant');
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

    public function getLogisticsUsers(Request $request)
    {
        $sorting = CollectionHelper::getSorting('users', 'name', $request->by, $request->order);

        return User::with('roles')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Logistics');
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

        $validatedData = $this->validateUserCreate($request, 'admin');
        $user = User::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        if ($request->roles) {
            $roles = Role::whereIn('name', $request->roles)->pluck('id');
            $user->roles()->attach($roles);
        }

        return response()->json($user->refresh()->load('roles'), 201);
    }

    public function storeShopUser(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $this->validateUserCreate($request, 'shop');
        $validatedData['shop_id'] = $this->getShopId($request->shop_slug);
        $user = User::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        $shopRoleId = Role::where('name', 'Shop')->first()->id;
        $user->roles()->attach($shopRoleId);

        return response()->json($user->refresh()->load(['shop']), 201);
    }

    public function storeRestaurantUser(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $this->validateUserCreate($request, 'restaurant');
        $validatedData['restaurant_branch_id'] = $this->getRestaruantBranchId($request->restaurant_branch_slug);
        $user = User::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        $restaurantRoleId = Role::where('name', 'Restaurant')->first()->id;
        $user->roles()->attach($restaurantRoleId);

        return response()->json($user->refresh()->load(['restaurantBranch', 'restaurantBranch.restaurant']), 201);
    }

    public function storeLogisticsUser(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $this->validateUserCreate($request);
        $user = User::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        $logisticRoleId = Role::where('name', 'Logistics')->first()->id;
        $user->roles()->attach($logisticRoleId);

        return response()->json($user->refresh()->load('roles'), 201);
    }

    private function getShopId($slug)
    {
        return Shop::where('slug', $slug)->first()->id;
    }

    private function getRestaruantBranchId($slug)
    {
        return RestaurantBranch::where('slug', $slug)->first()->id;
    }

    public function show(User $user)
    {
        return $user->load('roles');
    }

    public function update(Request $request, User $user)
    {
        $validatedData = $this->validateUserUpdate($request, $user->id);

        $user->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        $user->roles()->detach();

        if ($request->roles) {
            $roles = Role::whereIn('name', $request->roles)->pluck('id');
            $user->roles()->attach($roles);
        }

        return response()->json($user->refresh()->load('roles'), 200);
    }

    public function updateShopUser(Request $request, User $user)
    {
        $validatedData = $this->validateUserUpdate($request, $user->id, 'shop');
        $validatedData['shop_id'] = $this->getShopId($request->shop_slug);
        $user->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        return response()->json($user->refresh()->load(['shop']), 200);
    }

    public function updateRestaurantUser(Request $request, User $user)
    {
        $validatedData = $this->validateUserUpdate($request, $user->id, 'restaurant');
        $validatedData['restaurant_branch_id'] = $this->getRestaruantBranchId($request->restaurant_branch_slug);
        $user->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        return response()->json($user->refresh()->load(['restaurantBranch', 'restaurantBranch.restaurant']), 201);
    }

    public function updateLogisticsUser(Request $request, User $user)
    {
        $validatedData = $this->validateUserUpdate($request, $user->id);
        $user->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        return response()->json($user, 200);
    }

    public function destroy(User $user)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        if ($user->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 406);
        }

        foreach ($user->images as $image) {
            $this->deleteFile($image->slug);
        }

        $user->delete();
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

    private function validateUserCreate($request, $type = null)
    {
        $request['phone_number'] = PhoneNumber::make($request->phone_number, 'MM');

        $rules = [
            'slug' => 'required|unique:users',
            'username' => 'required|unique:users',
            'name' => 'required',
            'phone_number' => 'required|phone:MM|unique:users',
            'password' => 'required|min:6',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ];

        $rules = $this->getRulesByType($rules, $type);

        $messages = [
            'phone_number.phone' => 'Invalid phone number.',
        ];

        $validatedData = $request->validate($rules, $messages);
        $validatedData['password'] = Hash::make($validatedData['password']);
        return $validatedData;
    }

    private function validateUserUpdate($request, $userId, $type = null)
    {
        $request['phone_number'] = PhoneNumber::make($request->phone_number, 'MM');

        $rules = [
            'name' => 'required',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'phone_number' => [
                'required',
                'phone:MM',
                Rule::unique('users')->ignore($userId),
            ],
        ];

        $rules = $this->getRulesByType($rules, $type);

        $messages = [
            'phone_number.phone' => 'Invalid phone number.',
        ];

        $validatedData = $request->validate($rules, $messages);
        return $validatedData;
    }

    private function getRulesByType($rules, $type)
    {
        if ($type === 'shop') {
            $rules['shop_slug'] = 'required|exists:App\Models\Shop,slug';
        } elseif ($type === 'restaurant') {
            $rules['restaurant_branch_slug'] = 'required|exists:App\Models\RestaurantBranch,slug';
        } elseif ($type === 'admin') {
            $rules['roles'] = 'required|array';
            // $rules['roles.*'] = 'required|exists:App\Models\Role,slug';
        }

        return $rules;
    }

    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        if (Hash::check($request->current_password, Auth::guard('users')->user()->password)) {
            $user->update(['password' => Hash::make($request->new_password)]);
            return $this->generateResponse('The password has been successfully updated.', 200, true);
        }

        return $this->generateResponse('Your current password is incorrect.', 403, true);
    }

    public function updatePasswordForCustomer(Request $request, Customer $customer)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        if (Hash::check($request->current_password, Auth::guard('users')->user()->password)) {
            $customer->update(['password' => Hash::make($request->new_password)]);
            return $this->generateResponse('The password has been successfully updated.', 200, true);
        }

        return $this->generateResponse('Your current password is incorrect.', 403, true);
    }

    public function registerToken(Request $request)
    {
        $userId = Auth::guard('vendors')->user()->id;

        $jwt = str_replace("Bearer ", "", $request->header('Authorization'));
        $data['user_id'] = $userId;
        $data['jwt'] = $jwt;
        $data['device_token'] = $request->token;

        $userSession = UserSession::where('jwt', $jwt)->orWhere('device_token', $request->token)->first();

        if ($userSession) {
            $userSession['user_id'] = $userId;
            $userSession['jwt'] = $data['jwt'];
            $userSession['device_token'] = $data['device_token'];
            $userSession->update();
        } else {
            UserSession::create($data);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function getUserAuditLogs(Request $request, User $user)
    {
        return Audit::where('user_slug', $user->slug)
            ->whereBetween('create_at', array($request->from, $request->to))
            ->paginate(10);
    }
}
