<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\OneTimePassword;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class UserAuthController extends Controller
{
    use ResponseHelper, FileHelper;

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

            $adminRole = $user->roles->contains(function ($role) {
                return $role->name === 'Admin' || $role->name === "Logistics";
            });

            if (!$adminRole) {
                return false;
            }

            return Auth::guard('users')->claims($user->toArray())->attempt([
                'username' => $request->username,
                'password' => $request->password,
            ]);
        }

        return false;
    }

    public function logout()
    {
        Auth::guard('users')->logout();
        return response()->json(['message' => 'User successfully logged out.'], 200);
    }

    public function refreshToken()
    {
        return response()->json(['token' => Auth::guard('users')->refresh()], 200);
    }

    public function getProfile()
    {
        return response()->json(Auth::guard('users')->user());
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('users')->user();

        $validatedData = $request->validate(
            [
                'name' => 'required',
                'image_slug' => 'nullable|exists:App\Models\File,slug',
                'phone_number' => [
                    'required',
                    'phone:MM',
                    Rule::unique('users')->ignore($user->id),
                ],
            ],
            [
                'phone_number.phone' => 'Invalid phone number.',
            ],
        );

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $user->slug);
        }

        $user->update($validatedData);

        return response()->json($user, 200);
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

        $user = Auth::guard('users')->user();

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

        $fifteenMinutes = Carbon::parse($otp->created_at)->addMinutes(15);
        if ($fifteenMinutes->lt(Carbon::now())) {
            return $this->generateResponse('The OTP code is expired. Please send another one.', 406, true);
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
