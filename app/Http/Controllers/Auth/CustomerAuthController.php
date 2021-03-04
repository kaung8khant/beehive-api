<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
                'password' => 'required|string|min:6',
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

        $otp = OneTimePassword::where('phone_number', $validatedData['phone_number'])->latest()->first();

        if (!$otp || $otp->otp_code !== $validatedData['otp_code']) {
            return $this->generateResponse('The OTP code is incorrect.', 406, TRUE);
        }

        $customer = Customer::create($validatedData);
        $token = JWTAuth::claims($customer->refresh()->toArray())->fromUser($customer);

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
        $user = Auth::guard('customers')->user();
        return $this->generateResponse($user, 200);
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
                'gender' => 'required|in:Male,Female',
                'otp_code' => 'required|string',
            ],
            ['phone_number.phone' => 'Invalid phone number.']
        );
    }
}
