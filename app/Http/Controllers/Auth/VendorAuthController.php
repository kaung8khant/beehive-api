<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;
use App\Helpers\ResponseHelper;
use App\Models\User;
use App\Models\UserSession;
use App\Models\OneTimePassword;

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
                return response()->json(['message' => 'Your account is disabled. Contact admin for more information.'], 403);
            }

            return response()->json(['token' => $result], 200);
        }

        return response()->json(['message' => 'Username or password wrong.'], 401);
    }

    private function attemptLogin(Request $request)
    {
        $user = User::with('roles')->where('username', $request->username)->first();

        if ($user) {
            if (!$user->is_enable) {
                return 'disabled';
            }

            $vendorRole = $user->roles->contains(function ($role) {
                return $role->name === 'Shop' || $role->name === 'Restaurant';
            });

            if (!$vendorRole) {
                return false;
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

    public function refreshToken(Request $request)
    {
        $token = Auth::guard('vendors')->refresh();
        $userSession = UserSession::where('jwt', str_replace("Bearer ", "", $request->header('Authorization')))->first();
        $userSession->jwt = $token;
        $userSession->update();
        return response()->json(['token' => $token], 200);
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

    public function resetPassword(Request $request)
    {
        $request['phone_number'] = PhoneNumber::make($request->phone_number, 'MM');

        $validator = Validator::make(
            $request->all(),
            [
                'phone_number' => 'required|phone:MM|exists:App\Models\User,phone_number',
                'otp_code' => 'required|string',
                'password' => 'required|string|min:6',
            ],
            ['phone_number.phone' => 'Invalid phone number.']
        );

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $validatedData = $validator->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);

        $user = $this->getUserWithPhone($validatedData['phone_number']);
        $otp = $this->getOtp($validatedData['phone_number'], 'reset');

        if (!$otp || $otp->otp_code !== $validatedData['otp_code']) {
            return $this->generateResponse('The OTP code is incorrect.', 406, true);
        }

        if (Hash::check($request->password, $user->password)) {
            return $this->generateResponse('Your new password must not be same with old password.', 406, true);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $otp->update(['is_used' => 1]);

        return $this->generateResponse('Your password has been successfully reset.', 200, true);
    }

    private function getUserWithPhone($phoneNumber)
    {
        return User::where('phone_number', $phoneNumber)->firstOrFail();
    }

    private function getOtp($phoneNumber, $type)
    {
        return OneTimePassword::where('phone_number', $phoneNumber)
            ->where('type', $type)
            ->where('source', 'users')
            ->where('is_used', 0)
            ->latest()
            ->first();
    }
}
