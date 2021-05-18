<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Models\RestaurantBranch;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use App\Models\Customer;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class UserController extends Controller
{
    use FileHelper, StringHelper, ResponseHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/users",
     *      operationId="getUserLists",
     *      tags={"Users"},
     *      summary="Get list of users",
     *      description="Returns list of users",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *        name="filter",
     *        description="Filter",
     *        required=false,
     *        in="query",
     *        @OA\Schema(
     *            type="string"
     *        ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function index(Request $request)
    {
        return User::with('roles')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Admin');
            })
            ->where(function ($q) use ($request) {
                $q->where('username', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->paginate(10);
    }

    public function getShopUsers(Request $request)
    {
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
            ->paginate(10);
    }

    public function getRestaurantUsers(Request $request)
    {
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
            ->paginate(10);
    }

    public function getLogisticsUsers(Request $request)
    {
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
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/users",
     *      operationId="storeUser",
     *      tags={"Users"},
     *      summary="Create a user",
     *      description="Returns newly created user",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created user object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/User")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $this->validateUserCreate($request);
        $user = User::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        $adminRoleId = Role::where('name', 'Admin')->first()->id;
        $user->roles()->attach($adminRoleId);

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

    /**
     * @OA\Get(
     *      path="/api/v2/admin/users/{slug}",
     *      operationId="showUser",
     *      tags={"Users"},
     *      summary="Get One user",
     *      description="Returns a requested user",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested user",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function show($slug)
    {
        return User::with('roles')->where('slug', $slug)->firstOrFail();
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/users/{slug}",
     *      operationId="updateUser",
     *      tags={"Users"},
     *      summary="Update a user",
     *      description="Update a requested user",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a user",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New user data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/User")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function update(Request $request, $slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();

        $validatedData = $this->validateUserUpdate($request, $user->id);

        $user->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        return response()->json($user, 200);
    }

    public function updateShopUser(Request $request, $slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();

        $validatedData = $this->validateUserUpdate($request, $user->id, 'shop');
        $validatedData['shop_id'] = $this->getShopId($request->shop_slug);
        $user->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        return response()->json($user->refresh()->load(['shop']), 200);
    }

    public function updateRestaurantUser(Request $request, $slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();

        $validatedData = $this->validateUserUpdate($request, $user->id, 'restaurant');
        $validatedData['restaurant_branch_id'] = $this->getRestaruantBranchId($request->restaurant_branch_slug);
        $user->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        return response()->json($user->refresh()->load(['restaurantBranch', 'restaurantBranch.restaurant']), 201);
    }

    public function updateLogisticsUser(Request $request, $slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();

        $validatedData = $this->validateUserUpdate($request, $user->id);
        $user->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        return response()->json($user, 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/users/{slug}",
     *      operationId="deleteUser",
     *      tags={"Users"},
     *      summary="Delete One User",
     *      description="Delete one specific user",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested user",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function destroy($slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();

        if ($user->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 406);
        }

        $user->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/admin/users/toggle-enable/{slug}",
     *      operationId="enableUser",
     *      tags={"Users"},
     *      summary="Enable user",
     *      description="Enable a user",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the user",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function toggleEnable($slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();

        if ($user->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot change your own status.'], 406);
        }

        $user->is_enable = !$user->is_enable;
        $user->save();
        return response()->json(['message' => 'Success.'], 200);
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

    private function validateUserCreate($request, $type = null)
    {
        $request['phone_number'] = PhoneNumber::make($request->phone_number, 'MM');

        $rules = [
            'slug' => 'required|unique:users',
            'username' => 'required|unique:users',
            'name' => 'required',
            'phone_number' => 'required|phone:MM|unique:users',
            'password' => 'required|min:6',
            'image_slug' => 'nullable|exists:App\Models\File,slug'
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
        } else if ($type === 'restaurant') {
            $rules['restaurant_branch_slug'] = 'required|exists:App\Models\RestaurantBranch,slug';
        }

        return $rules;
    }

    public function updatePassword(Request $request, $slug)
    {

        $user = User::where('slug', $slug)->firstOrFail();

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

    public function updatePasswordForCustomer(Request $request, $slug)
    {
        $customer = Customer::where('slug', $slug)->firstOrFail();

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
}
