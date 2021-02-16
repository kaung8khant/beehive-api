<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserAuthController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/v2/admin/login",
     *      operationId="adminLogin",
     *      tags={"UserAuth"},
     *      summary="User login",
     *      description="Login as a system user",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Login credentials",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(
     *                  @OA\Property(property="username", type="string", example="admin"),
     *                  @OA\Property(property="password", type="string", example="password")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      )
     *)
     */
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

        return response()->json(['message' => 'Username or password wrong.'], 401);
    }

    /**
     * Attempt login the user via username and password.
     *
     * @return mixed
     */
    private function attemptLogin(Request $request)
    {
        $user = User::with('roles')->where('username', $request->username)->first();

        if ($user) {
            if (!$user->is_enable) {
                return 'disabled';
            }

            return Auth::guard('users')->claims($user->toArray())->attempt([
                'username' => $request->username,
                'password' => $request->password,
            ]);
        }

        return false;
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::guard('users')->logout();
        return response()->json(['message' => 'User successfully logged out.'], 200);
    }

    /**
     * Refresh the token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken()
    {
        return response()->json(['token' => Auth::guard('users')->refresh()], 200);
    }

    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAuthenticatedUser()
    {
        return response()->json(Auth::guard('users')->user());
    }
}
