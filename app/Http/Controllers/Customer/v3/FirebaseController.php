<?php

namespace App\Http\Controllers\Customer\v3;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Firebase\Auth\Token\Exception\InvalidToken;

class FirebaseController extends Controller
{
    private $auth;

    public function __construct()
    {
        $this->auth = app('firebase.auth');
    }

    public function login()
    {
        $customClaims = [
            'custom_claims' => [
                'phone_number' => request('phone_number'),
                'email' => request('email'),
                'name' => request('name'),
            ],
        ];

        $customToken = $this->auth->createCustomToken(request('phone_number'), $customClaims);
        $signInResult = $this->auth->signInWithCustomToken($customToken);

        return response()->json($signInResult->asTokenResponse());
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

        $uid = $verifiedIdToken->claims()->get('sub');
        $customer = Customer::where('phone_number', $uid)->first();

        if ($customer) {

        } else {
            Customer::create($verifiedIdToken->claims()->get('custom_claims'));
        }

        $user = $this->auth->getUser($uid);

        $user->customClaims = (array) $verifiedIdToken->claims()->get('custom_claims');
        return $user;
    }
}
