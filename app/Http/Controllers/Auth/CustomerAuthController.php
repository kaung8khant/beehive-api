<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OneTimePassword;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomerAuthController extends Controller
{
    use StringHelper, ResponseHelper;

    public function login(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'phone_number' => 'required|phone:MM',
                'password' => 'required|string',
            ],
            ['phone_number.phone' => 'Invalid phone number.']
        );

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $result = $this->attemptLogin($request);

        if ($result) {
            if ($result === 'disabled') {
                return $this->generateResponse('Your accout is disabled. Contact support for more information.', 403, true);
            }

            return $this->generateResponse(['token' => $result], 200);
        }

        return $this->generateResponse('Your phone number or password is incorrect.', 401, true);
    }

    private function attemptLogin(Request $request)
    {
        $phoneNumber = PhoneNumber::make($request->phone_number, 'MM');
        $customer = Customer::where('phone_number', $phoneNumber)->first();

        if ($customer) {
            if (!$customer->is_enable) {
                return 'disabled';
            }

            return Auth::guard('customers')->claims($customer->toArray())->attempt([
                'phone_number' => $phoneNumber,
                'password' => $request->password,
            ]);
        }

        return false;
    }

    public function register(Request $request)
    {
        $validator = $this->validateRegister($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $validatedData = $validator->validated();

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');
        $customer = Customer::where('phone_number', $validatedData['phone_number'])->first();

        if ($customer && $customer->created_by === 'customer') {
            return $this->generateResponse('The phone number has already been taken.', 422, true);
        }

        $validatedData['password'] = Hash::make($validatedData['password']);
        $validatedData['created_by'] = 'customer';

        $otp = $this->getOtp($validatedData['phone_number'], 'register');

        if (!$otp || $otp->otp_code !== $validatedData['otp_code']) {
            return $this->generateResponse('The OTP code is incorrect.', 406, true);
        }

        $fifteenMinutes = Carbon::parse($otp->created_at)->addMinutes(15);
        if (Carbon::now()->gt($fifteenMinutes)) {
            return $this->generateResponse('The OTP code is expired. Please send another one.', 406, true);
        }

        if ($customer) {
            $customer->update($validatedData);
        } else {
            $customer = Customer::create($validatedData);
        }

        $token = JWTAuth::claims($customer->refresh()->toArray())->fromUser($customer);

        $otp->update(['is_used' => 1]);
        return $this->generateResponse(['token' => $token], 200);
    }

    public function logout()
    {
        Auth::guard('customers')->logout();
        return $this->generateResponse('Customer successfully logged out.', 200, true);
    }

    public function refreshToken()
    {
        $token = Auth::guard('customers')->refresh();
        return $this->generateResponse(['token' => $token], 200);
    }

    public function getProfile()
    {
        $customer = Auth::guard('customers')->user()->makeVisible(['id'])->load('addresses');
        return $this->generateResponse($customer, 200);
    }

    public function updateProfile(Request $request)
    {
        $customer = Auth::guard('customers')->user();

        $validator = Validator::make($request->all(), [
            'email' => [
                'nullable',
                'email',
                Rule::unique('customers')->ignore($customer->id),
            ],
            'name' => 'required|max:255',
            'gender' => 'nullable|in:Male,Female',
            'date_of_birth' => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $customer->update($validator->validated());
        return $this->generateResponse($customer, 200);
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

        $customer = Auth::guard('customers')->user();

        if (Hash::check($request->old_password, $customer->password)) {
            $customer->update(['password' => Hash::make($request->new_password)]);
            return $this->generateResponse('Your password has been successfully updated.', 200, true);
        }

        return $this->generateResponse('Your old password is incorrect.', 403, true);
    }

    private function validateRegister($request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        return Validator::make(
            $request->all(),
            [
                'slug' => 'required|unique:customers',
                'email' => 'nullable|email|unique:customers',
                'name' => 'required|max:255',
                'phone_number' => 'required|phone:MM',
                'password' => 'required|string|min:6',
                'gender' => 'nullable|in:Male,Female',
                'date_of_birth' => 'nullable|date_format:Y-m-d',
                'otp_code' => 'required|string',
            ],
            ['phone_number.phone' => 'Invalid phone number.']
        );
    }

    public function resetPassword(Request $request)
    {
        $request['phone_number'] = PhoneNumber::make($request->phone_number, 'MM');

        $validator = Validator::make(
            $request->all(),
            [
                'phone_number' => 'required|phone:MM|exists:App\Models\Customer,phone_number',
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

        $customer = $this->getCustomerWithPhone($validatedData['phone_number']);
        $otp = $this->getOtp($validatedData['phone_number'], 'reset');

        if (!$otp || $otp->otp_code !== $validatedData['otp_code']) {
            return $this->generateResponse('The OTP code is incorrect.', 406, true);
        }

        $fifteenMinutes = Carbon::parse($otp->created_at)->addMinutes(15);
        if (Carbon::now()->gt($fifteenMinutes)) {
            return $this->generateResponse('The OTP code is expired. Please send another one.', 406, true);
        }

        if (Hash::check($request->password, $customer->password)) {
            return $this->generateResponse('Your new password must not be same with old password.', 406, true);
        }

        $customer->update(['password' => Hash::make($request->password)]);
        $otp->update(['is_used' => 1]);

        return $this->generateResponse('Your password has been successfully reset.', 200, true);
    }

    public function getFavoritesCount()
    {
        $customer = Auth::guard('customers')->user();

        $favoriteRestaurants = $customer->favoriteRestaurants()->count();
        $favoriteProducts = $customer->favoriteProducts()->count();

        return $this->generateResponse(['favorites_count' => $favoriteRestaurants + $favoriteProducts], 200);
    }

    private function getCustomerWithPhone($phoneNumber)
    {
        return Customer::where('phone_number', $phoneNumber)->firstOrFail();
    }

    private function getOtp($phoneNumber, $type)
    {
        return OneTimePassword::where('phone_number', $phoneNumber)
            ->where('type', $type)
            ->where('source', 'customers')
            ->where('is_used', 0)
            ->latest()
            ->first();
    }
}
