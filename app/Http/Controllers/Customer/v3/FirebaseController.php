<?php

namespace App\Http\Controllers\Customer\v3;

use App\Http\Controllers\Controller;
use Firebase\Auth\Token\Exception\InvalidToken;
use Illuminate\Support\Str;

class FirebaseController extends Controller
{
    private $auth;

    public function __construct()
    {
        $this->auth = app('firebase.auth');
    }

    public function login() // login (or) register
    {
        $uid = '+959450026655';
        $customClaims = [
            'custom_claims' => [
                'email' => 'test@example.com',
            ],
        ];

        $customToken = $this->auth->createCustomToken($uid, $customClaims);
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
        $user = $this->auth->getUser($uid);

        $user->customClaims = (array) $verifiedIdToken->claims()->get('custom_claims');
        return $user;
    }
}
