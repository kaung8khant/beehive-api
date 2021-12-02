<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ResponseHelper;
use App\Helpers\SmsHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OneTimePassword;
use App\Services\MessageService\BoomSmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;

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

        if ($checkRegister && $checkRegister->created_by === 'customer') {
            return $this->generateResponse('The phone number has already been taken.', 422, true);
        }

        return $this->sendOtp($phoneNumber, 'register', 'customers');
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
            $request['source'] = 'customers';
            $model = config('model.customers');
        }

        $checkUser = $model::where('phone_number', $phoneNumber)->first();

        if (!$checkUser) {
            return $this->generateResponse('There is no user with this phone number.', 404, true);
        }

        return $this->sendOtp($phoneNumber, 'reset', $request->source);
    }

    private function sendOtp($phoneNumber, $type, $source)
    {
        $otpCode = rand(100000, 999999);
        $otp = $this->getOtp($phoneNumber, $type, $source);

        if ($otp) {
            $oneMinute = Carbon::parse($otp->created_at)->addMinute();

            if ($oneMinute->gt(Carbon::now())) {
                return $this->generateResponse('You can send another code after one minute.', 403, true);
            }
        }

        $smsData = SmsHelper::prepareSmsData($otpCode);

        try {
            $messageService = new BoomSmsService();
            $smsResponse = $messageService->sendMessage($phoneNumber, 'Your OTP code is ' . $otpCode . '.');

            if ($smsResponse['status'] !== 0) {
                $this->storeOtp($phoneNumber, $otpCode, $smsResponse['message_id'], 'Failed', $type, $source);
                return $this->generateResponse('Something went wrong when sending OTP.', 406, true);
            }

            $this->storeOtp($phoneNumber, $otpCode, $smsResponse['message_id'], 'Success', $type, $source);
            SmsHelper::storeSmsLog($smsData, $smsResponse, $phoneNumber, 'otp', 'Success');
        } catch (\Exception $e) {
            Log::critical($e);
            SmsHelper::storeSmsLog($smsData, null, $phoneNumber, 'otp', 'Error');
        }

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

    private function getOtp($phoneNumber, $type, $source)
    {
        return OneTimePassword::where('phone_number', $phoneNumber)
            ->where('type', $type)
            ->where('source', $source)
            ->where('is_used', 0)
            ->latest()
            ->first();
    }

    private function storeOtp($phoneNumber, $otpCode, $messageId, $status, $type, $source)
    {
        OneTimePassword::create([
            'phone_number' => $phoneNumber,
            'otp_code' => $otpCode,
            'status' => $status,
            'message_id' => $messageId,
            'type' => $type,
            'source' => $source,
        ]);
    }

    public function checkOtpToRegister(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'phone_number' => 'required|phone:MM',
                'otp_code' => 'required',
            ],
            [
                'phone_number.phone' => 'Invalid phone number.',
            ]
        );

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $phoneNumber = PhoneNumber::make($request->phone_number, 'MM');
        $otp = OneTimePassword::where('phone_number', $phoneNumber)
            ->where('source', 'customers')
            ->where('is_used', 0)
            ->latest()
            ->first();

        if (!$otp || $otp->otp_code !== $request->otp_code) {
            return $this->generateResponse('The OTP code is incorrect.', 406, true);
        }

        $fifteenMinutes = Carbon::parse($otp->created_at)->addMinutes(15);
        if (Carbon::now()->gt($fifteenMinutes)) {
            return $this->generateResponse('The OTP code is expired. Please send another one.', 406, true);
        }

        return $this->generateResponse('success', 200, true);
    }
}
