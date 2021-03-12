<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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


    /**
     * @OA\Put(
     *      path="/api/v2/admin/profile/update",
     *      operationId="updateProfile",
     *      tags={"UserAuth"},
     *      summary="Update profile",
     *      description="Update profile",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Profile data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(
     *                  @OA\Property(property="name", type="string", example="name"),
     *                  @OA\Property(property="phone_number", type="string", example="phone_number"),
     *                  @OA\Property(property="username", type="string", readOnly=true)
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('users')->user();

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

        return response()->json($user, 200);
    }
}
