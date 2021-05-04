<?php

namespace App\Http\Controllers\Sms;

use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsController extends Controller
{
    protected $phonesPerWorker;

    public function __construct()
    {
        $this->phonesPerWorker = 200;
    }

    public function send(Request $request)
    {
        $this->validateRequest($request);

        $userId = Auth::guard('users')->user()->id;
        $message = trim(SmsHelper::removeEmoji($request->message));
        $smsData = SmsHelper::prepareSmsData($message, $userId);
        $workerCount = $this->calculateWorkerCount($request->phone_numbers);

        for ($i = 0; $i < $workerCount; $i++) {
            $uniqueKey = StringHelper::generateUniqueSlug();
            $phoneNumbers = array_slice($request->phone_numbers, $i * $this->phonesPerWorker, $this->phonesPerWorker);
            SendSms::dispatch($uniqueKey, $phoneNumbers, $message, $request->type, $smsData);
        }

        return response()->json(['Your sms is preparing and being sent to users.'], 200);
    }

    private function validateRequest($request)
    {
        $request->validate([
            'phone_numbers' => 'required|array|max:10000',
            'message' => 'required',
            'type' => 'required|in:otp,marketing',
        ]);
    }

    private function calculateWorkerCount($phoneNumbers)
    {
        $phoneNumberCount = count($phoneNumbers);
        $workerCount = intval($phoneNumberCount / $this->phonesPerWorker);

        if ($phoneNumberCount % $this->phonesPerWorker !== 0) {
            $workerCount += 1;
        }

        return $workerCount;
    }
}
