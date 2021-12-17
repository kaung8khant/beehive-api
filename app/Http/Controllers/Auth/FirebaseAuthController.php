<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Firebase\Auth\Token\Exception\InvalidToken;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;

class FirebaseAuthController extends Controller
{
    private $auth;

    public function __construct()
    {
        $this->auth = app('firebase.auth');
    }

    public function verify()
    {
        try {
            $verifiedIdToken = $this->auth->verifyIdToken(request('token'));
        } catch (InvalidToken $e) {
            return 'The token is invalid: ' . $e->getMessage();
        } catch (\InvalidArgumentException $e) {
            return 'The token could not be parsed: ' . $e->getMessage();
        }

        $validator = Validator::make(request()->all(), [
            'email' => 'nullable|email',
            'name' => 'required|string',
            'phone_number' => 'required|phone:MM',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::generateResponse($validator->errors()->first(), 422, true);
        }

        $uid = $verifiedIdToken->claims()->get('sub');
        $provider = $verifiedIdToken->claims()->get('firebase')['sign_in_provider'];

        $phoneNumber = PhoneNumber::make(request('phone_number'), 'MM');
        $customer = Customer::where('phone_number', $phoneNumber)->first();

        if ($provider === 'anonymous') {
            $this->auth->deleteUser($uid);
            $this->createCustomUser($phoneNumber);
        }

        if (!$customer) {
            $customer = Customer::create([
                'slug' => StringHelper::generateUniqueSlugWithTable('customers'),
                'email' => request('email'),
                'name' => request('name'),
                'phone_number' => $phoneNumber,
                'password' => isset($data['password']) ? bcrypt($data['password']) : bcrypt(StringHelper::generateRandomPassword()),
            ]);
        }

        $token = auth('customers')->claims($customer->toArray())->login($customer);
        return ResponseHelper::generateResponse(['token' => $token], 200);
    }

    public function createCustomUser($phoneNumber)
    {
        // $customClaims = [
        //     'custom_claims' => [
        //         'name' => request('name'),
        //         'phone_number' => request('phone_number'),
        //         'email' => request('email') ?? null,
        //         'password' => request('password') ?? null,
        //     ],
        // ];

        $customToken = $this->auth->createCustomToken((string) $phoneNumber);
        $signInResult = $this->auth->signInWithCustomToken($customToken);

        return response()->json($signInResult->asTokenResponse());
    }
}
