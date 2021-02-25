<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Helpers\StringHelper;
use App\Models\Customer;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomerAuthController extends Controller
{
    use StringHelper;

    public function login(Request $request)
    {
        $request->validate([
            'credential' => 'required|string|max:100',
            'password' => 'required|string|min:6',
            'type' => 'required|in:username,phone_number',
        ]);

        $result = $this->attemptLogin($request);

        if ($result) {
            if ($result === 'disabled') {
                return response()->json(['message' => 'Your accout is disabled. Contact support for more information.'], 403);
            }

            return response()->json(['token' => $result], 200);
        }

        return response()->json(['message' => 'Username or password wrong.'], 401);
    }

    private function attemptLogin(Request $request)
    {
        $credential = $request->type == 'username' ? 'username' : 'phone_number';
        $customer = Customer::where($credential, $request->credential)->first();

        if ($customer) {
            if (!$customer->is_enable) {
                return 'disabled';
            }

            return Auth::guard('customers')->claims($customer->toArray())->attempt([
                $credential => $request->credential,
                'password' => $request->password,
            ]);
        }

        return false;
    }

    public function register(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate([
            'slug' => 'required|unique:customers',
            'username' => 'required|string|min:3|max:100|unique:customers',
            'email' => 'required|email|unique:customers',
            'name' => 'required|max:255',
            'phone_number' => 'required|unique:customers',
            'password' => 'required|string|confirmed|min:6',
            'gender' => 'required|in:male,female',
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);

        $customer = Customer::create($validatedData);
        $token = JWTAuth::claims($customer->toArray())->fromUser($customer->refresh());

        return response()->json(['token' => $token], 201);
    }

    public function logout()
    {
        Auth::guard('customers')->logout();
        return response()->json(['message' => 'Customer successfully logged out.'], 200);
    }

    public function refreshToken()
    {
        return response()->json(['token' => Auth::guard('customers')->refresh()], 200);
    }

    public function getProfile()
    {
        return response()->json(Auth::guard('customers')->user());
    }
}
