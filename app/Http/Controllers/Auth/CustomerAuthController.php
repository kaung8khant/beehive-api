<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;
use Propaganistas\LaravelPhone\PhoneNumber;
use App\Helpers\StringHelper;
use App\Helpers\ResponseHelper;
use App\Models\Customer;
use App\Models\OneTimePassword;

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
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $result = $this->attemptLogin($request);

        if ($result) {
            if ($result === 'disabled') {
                return $this->generateResponse('Your accout is disabled. Contact support for more information.', 403, TRUE);
            }

            return $this->generateResponse(['token' => $result], 200);
        }

        return $this->generateResponse('Your phone number or password is incorrect.', 401, TRUE);
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
        $request['slug'] = $this->generateUniqueSlug();
        $request['phone_number'] = PhoneNumber::make($request->phone_number, 'MM');

        $validator = $this->validateRegister($request);

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $validatedData = $validator->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);

        $otp = $this->getOtp($validatedData['phone_number'], 'register');

        if (!$otp || $otp->otp_code !== $validatedData['otp_code']) {
            return $this->generateResponse('The OTP code is incorrect.', 406, TRUE);
        }

        $customer = Customer::create($validatedData);
        $token = JWTAuth::claims($customer->refresh()->toArray())->fromUser($customer);

        $otp->update(['is_used' => 1]);
        return $this->generateResponse(['token' => $token], 200);
    }

    public function logout()
    {
        Auth::guard('customers')->logout();
        return $this->generateResponse('Customer successfully logged out.', 200, TRUE);
    }

    public function refreshToken()
    {
        $token = Auth::guard('customers')->refresh();
        return $this->generateResponse(['token' => $token], 200);
    }

    public function getProfile()
    {
        $customerId = Auth::guard('customers')->user()->id;
        $customer = Customer::with('addresses')->where('id', $customerId)->firstOrFail();
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
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
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
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $customer = Auth::guard('customers')->user();

        if (Hash::check($request->old_password, $customer->password)) {
            $customer->update(['password' => Hash::make($request->new_password)]);
            return $this->generateResponse('Your password has been successfully updated.', 200, TRUE);
        }

        return $this->generateResponse('Your old password is incorrect.', 403, TRUE);
    }

    private function validateRegister($request)
    {
        return Validator::make(
            $request->all(),
            [
                'slug' => 'required|unique:customers',
                'email' => 'nullable|email|unique:customers',
                'name' => 'required|max:255',
                'phone_number' => 'required|phone:MM|unique:customers',
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
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $validatedData = $validator->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);

        $customer = $this->getCustomerWithPhone($validatedData['phone_number']);
        $otp = $this->getOtp($validatedData['phone_number'], 'reset');

        if (!$otp || $otp->otp_code !== $validatedData['otp_code']) {
            return $this->generateResponse('The OTP code is incorrect.', 406, TRUE);
        }

        if (Hash::check($request->password, $customer->password)) {
            return $this->generateResponse('Your new password must not be same with old password.', 406, TRUE);
        }

        $customer->update(['password' => Hash::make($request->new_password)]);
        $otp->update(['is_used' => 1]);

        return $this->generateResponse('Your password has been successfully reset.', 200, TRUE);
    }

    public function getFavoritesCount()
    {
        $customer = Auth::guard('customers')->user();

        $favoriteRestaurants = $customer->favoriteRestaurants()->count();
        $favoriteProducts = $customer->product()->count();

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
            ->where('is_used', 0)
            ->latest()
            ->first();
    }
}
