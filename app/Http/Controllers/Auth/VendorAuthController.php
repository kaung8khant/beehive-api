<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class VendorAuthController extends Controller
{
    use ResponseHelper;

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|min:6',
        ]);

        $result = $this->attemptLogin($request);

        if ($result) {
            if ($result === 'disabled') {
                return response()->json(['message' => 'Your accout is disabled. Contact admin for more information.'], 403);
            }

            return response()->json(['token' => $result], 200);
        }

        return response()->json(['message' => 'Username or password wrong.' . Hash::make($request->password)], 401);
    }

    private function attemptLogin(Request $request)
    {
        $user = User::with('roles')->where('username', $request->username)->first();

        if ($user) {
            if (!$user->is_enable) {
                return 'disabled';
            }

            return Auth::guard('vendors')->claims($user->toArray())->attempt([
                'username' => $request->username,
                'password' => $request->password,
            ]);
        }

        return false;
    }

    public function logout()
    {
        Auth::guard('vendors')->logout();
        return response()->json(['message' => 'User successfully logged out.'], 200);
    }

    public function refreshToken()
    {
        return response()->json(['token' => Auth::guard('vendors')->refresh()], 200);
    }

    public function getProfile()
    {
        $user = User::with('shop', 'restaurantBranch', 'restaurantBranch.restaurant', 'roles', 'restaurantBranch.township', 'restaurantBranch.restaurant.availableTags', 'restaurantBranch.restaurant.availableCategories', 'shop.township', 'shop.availableTags', 'shop.availableCategories')->where('id', Auth::guard('vendors')->user()->id)->get();

        return response()->json($user);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('vendors')->user();
        $user->update($request->validate([
            'username' => [
                'required',
                Rule::unique('users')->ignore($user->id),
            ],
            'name' => 'required',
            'phone_number' => [
                'required',
                Rule::unique('users')->ignore($user->id),
            ],
        ]));

        return response()->json($request, 200);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $user = Auth::guard('vendors')->user();

        if (Hash::check($request->old_password, $user->password)) {
            $user->update(['password' => Hash::make($request->new_password)]);
            return $this->generateResponse('Your password has been successfully updated.', 200, true);
        }

        return $this->generateResponse('Your old password is incorrect.', 403, true);
    }
}
