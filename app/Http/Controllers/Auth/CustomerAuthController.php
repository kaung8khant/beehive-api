<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Helpers\StringHelper;
use App\Helpers\ResponseHelper;
use App\Models\Customer;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomerAuthController extends Controller
{
    use StringHelper;
    use ResponseHelper;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

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
        $customer = Customer::where('phone_number', $request->phone_number)->first();

        if ($customer) {
            if (!$customer->is_enable) {
                return 'disabled';
            }

            return Auth::guard('customers')->claims($customer->toArray())->attempt([
                'phone_number' => $request->phone_number,
                'password' => $request->password,
            ]);
        }

        return false;
    }

    public function register(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validator = Validator::make($request->all(), [
            'slug' => 'required|unique:customers',
            'username' => 'required|string|min:3|max:100|unique:customers',
            'email' => 'nullable|email|unique:customers',
            'name' => 'required|max:255',
            'phone_number' => 'required|unique:customers',
            'password' => 'required|string|confirmed|min:6',
            'gender' => 'required|in:male,female',
        ]);

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $validatedData = $validator->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);

        $customer = Customer::create($validatedData);
        $token = JWTAuth::claims($customer->toArray())->fromUser($customer->refresh());

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
}
