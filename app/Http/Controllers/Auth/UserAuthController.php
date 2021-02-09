<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserAuthController extends Controller
{
    /**
     * Get JWT token via username and password.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validatedRequest = $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|min:6',
        ]);

        $user = User::with('roles')
            ->where('username', $request->username)
            ->first();

        if (!$token = Auth::claims($user->toArray())->attempt($validatedRequest)) {
            return response()->json(['message' => 'Username or password wrong.'], 401);
        }

        return response()->json(['token' => $token], 200);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'User successfully logged out.'], 200);
    }

    /**
     * Refresh the token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken()
    {
        return response()->json(['token' => Auth::refresh()], 200);
    }

    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAuthenticatedUser()
    {
        return response()->json(Auth::user());
    }
}
