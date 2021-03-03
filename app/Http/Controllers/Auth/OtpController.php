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

class OtpController extends Controller
{
    use ResponseHelper;

    public function sendOtp(Request $request)
    {
        $validator = $this->validatePhoneNumber($request);

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $otpCode = rand(100000, 999999);
        $checkNumber = OneTimePassword::where('phone_number', $request->phone_number)->latest()->first();

        if ($checkNumber) {
            $fifteenMinutes = Carbon::parse($checkNumber->created_at)->addMinutes(15);

            if ($fifteenMinutes->gt(Carbon::now())) {
                $remainingTime = $fifteenMinutes->diff(Carbon::now())->format('%i');
                return $this->generateResponse('You can send another code after ' . $remainingTime . ' minutes.', 403, TRUE);
            }
        }

        $smsResponse = SmsHelper::send($request->phone_number, 'Your OTP code is ' . $otpCode . '.');

        if ($smsResponse['status'] !== 0) {
            $this->storeOtp($request->phone_number, $otpCode, $smsResponse['message_id'], 'Error');
            return $this->generateResponse('Something went wrong when sending OTP.', 406, TRUE);
        }

        $this->storeOtp($request->phone_number, $otpCode, $smsResponse['message_id'], 'Success');
        return $this->generateResponse('Otp code has been successfully sent to your phone.', 200, TRUE);
    }

    private function validatePhoneNumber($request)
    {
        return Validator::make(
            $request->all(),
            ['phone_number' => 'required|phone:MM|unique:customers'],
            ['phone_number.phone' => 'Invalid phone number.']
        );
    }

    private function storeOtp($phoneNumber, $otpCode, $messageId, $status)
    {
        OneTimePassword::create([
            'phone_number' => PhoneNumber::make($phoneNumber, 'MM'),
            'otp_code' => $otpCode,
            'status' => $status,
            'message_id' => $messageId,
        ]);
    }
}
