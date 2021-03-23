<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;
use Carbon\Carbon;
use App\Helpers\ResponseHelper;
use App\Helpers\SmsHelper;
use App\Models\OneTimePassword;
use App\Models\Customer;
use App\Models\User;

class OtpController extends Controller
{
    use ResponseHelper;

    public function sendOtpToRegister(Request $request)
    {
        $validator = $this->validatePhoneNumber($request, true);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $phoneNumber = PhoneNumber::make($request->phone_number, 'MM');
        $checkRegister = Customer::where('phone_number', $phoneNumber)->first();

        if ($checkRegister) {
            return $this->generateResponse('The phone number has already been taken.', 422, true);
        }

        return $this->sendOtp($phoneNumber, 'register');
    }

    public function forgotPassword(Request $request)
    {
        $validator = $this->validatePhoneNumber($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $phoneNumber = PhoneNumber::make($request->phone_number, 'MM');

        if ($request->source) {
            $model = config('model.' . $request->source);
        } else {
            $model = config('model.customers');
        }

        $checkUser = $model::where('phone_number', $phoneNumber)->first();

        if (!$checkUser) {
            return $this->generateResponse('There is no user with this phone number.', 404, true);
        }

        return $this->sendOtp($phoneNumber, 'reset');
    }

    private function sendOtp($phoneNumber, $type)
    {
        $otpCode = rand(100000, 999999);
        $checkNumber = $this->checkOtp($phoneNumber, $type);

        if ($checkNumber) {
            $fifteenMinutes = Carbon::parse($checkNumber->created_at)->addMinutes(15);

            if ($fifteenMinutes->gt(Carbon::now())) {
                $remainingTime = $fifteenMinutes->diff(Carbon::now())->format('%i');
                return $this->generateResponse('You can send another code after ' . $remainingTime . ' minutes.', 403, true);
            }
        }

        $smsResponse = SmsHelper::send($phoneNumber, 'Your OTP code is ' . $otpCode . '.');

        if ($smsResponse['status'] !== 0) {
            $this->storeOtp($phoneNumber, $otpCode, $smsResponse['message_id'], 'Error', $type);
            return $this->generateResponse('Something went wrong when sending OTP.', 406, true);
        }

        $this->storeOtp($phoneNumber, $otpCode, $smsResponse['message_id'], 'Success', $type);
        return $this->generateResponse('Otp code has been successfully sent to your phone.', 200, true);
    }

    private function validatePhoneNumber($request)
    {
        return Validator::make(
            $request->all(),
            ['phone_number' => 'required|phone:MM'],
            ['phone_number.phone' => 'Invalid phone number.']
        );
    }

    private function checkOtp($phoneNumber, $type)
    {
        return OneTimePassword::where('phone_number', $phoneNumber)
            ->where('type', $type)
            ->where('is_used', 0)
            ->latest()
            ->first();
    }

    private function storeOtp($phoneNumber, $otpCode, $messageId, $status, $type)
    {
        OneTimePassword::create([
            'phone_number' => $phoneNumber,
            'otp_code' => $otpCode,
            'status' => $status,
            'message_id' => $messageId,
            'type' => $type,
        ]);
    }
}
