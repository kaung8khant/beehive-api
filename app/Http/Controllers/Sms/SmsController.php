<?php

namespace App\Http\Controllers\Sms;

use App\Helpers\SmsHelper;
use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Phlib\SmsLength\SmsLength;
use Propaganistas\LaravelPhone\PhoneNumber;

class SmsController extends Controller
{
    public function send(Request $request)
    {
        $this->validateRequest($request);

        $message = trim(SmsHelper::removeEmoji($request->message));
        $smsData = $this->prepareSmsData($message);

        // return $jobCount = count($request->phone_numbers) % 100;

        foreach ($request->phone_numbers as $number) {
            $validator = $this->validateNumber($number);

            if (!$validator->fails()) {
                $phoneNumber = PhoneNumber::make($number, 'MM');

                try {
                    $smsResponse = SmsHelper::sendSms($phoneNumber, $message);
                    $status = 'Success';

                    if ($smsResponse['status'] !== 0) {
                        $status = 'Failed';
                    }

                    $this->storeSmsLog($smsData, $smsResponse, $phoneNumber, $request->type, $status);

                } catch (\Exception $e) {
                    Log::critical($e);
                    $this->storeSmsLog($smsData, null, $phoneNumber, $request->type, 'Error');
                }
            } else {
                $this->storeSmsLog($smsData, null, $number, $request->type, 'Rejected');
            }
        }

        return response()->json(['Your sms is preparing and being sent to users.'], 200);
    }

    private function prepareSmsData($message)
    {
        $smsLength = new SmsLength($message);

        return [
            'batchId' => Carbon::now()->getPreciseTimestamp(2),
            'message' => $message,
            'totalCharacters' => $smsLength->getSize(),
            'messageParts' => $smsLength->getMessageCount(),
            'encoding' => $smsLength->getEncoding() === 'ucs-2' ? 'Unicode' : 'Plain Text',
            'userId' => Auth::guard('users')->user()->id,
        ];
    }

    private function storeSmsLog($smsData, $smsResponse, $phoneNumber, $type, $status)
    {
        $params = [
            'batch_id' => $smsData['batchId'],
            'message_id' => isset($smsResponse['message_id']) ? $smsResponse['message_id'] : null,
            'phone_number' => $phoneNumber,
            'message' => $smsData['message'],
            'message_parts' => $smsData['messageParts'],
            'total_characters' => $smsData['totalCharacters'],
            'encoding' => $smsData['encoding'],
            'type' => $type,
            'status' => $status,
            'user_id' => $smsData['userId'],
        ];

        if ($status === 'Failed') {
            $params['error_message'] = $smsResponse['error-text'];
        } else if ($status === 'Error') {
            $params['error_message'] = 'Internal Server Error';
        }

        SmsLog::create($params);
    }

    private function validateRequest($request)
    {
        $request->validate([
            'phone_numbers' => 'required|array|max:10000',
            'message' => 'required',
            'type' => 'required|in:otp,marketing',
        ]);
    }

    private function validateNumber($number)
    {
        return Validator::make(['number' => $number], [
            'number' => 'phone:MM',
        ]);
    }
}
