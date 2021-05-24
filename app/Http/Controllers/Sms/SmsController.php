<?php

namespace App\Http\Controllers\Sms;

use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\SmsCampaign;
use App\Models\SmsLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;

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
        $this->createSmsCampaign($request, $smsData['batchId']);

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
            'name' => 'required|string',
            'description' => 'nullable',
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

    private function createSmsCampaign($request, $batchId)
    {
        SmsCampaign::create([
            'batch_id' => $batchId,
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'sent_at' => Carbon::now(),
        ]);
    }

    public function getLogs()
    {
        return SmsLog::with('user')->paginate(10);
    }

    public function getLogsByBatchId($batchId)
    {
        return SmsLog::with('user')->where('batch_id', $batchId)->paginate(10);
    }

    public function getLogsByPhone($phone)
    {
        $validator = Validator::make(
            ['phone' => $phone],
            ['phone' => 'phone:MM'],
            ['phone.phone' => 'Invalid phone number.']
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $phoneNumber = PhoneNumber::make($phone, 'MM');
        return SmsLog::with('user')->where('phone_number', $phoneNumber)->paginate(10);
    }

    public function getLogsByDate($from, $to)
    {
        $validator = Validator::make(
            [
                'from' => $from,
                'to' => $to,
            ],
            [
                'from' => 'required|date_format:Y-m-d',
                'to' => 'required|date_format:Y-m-d',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $from = Carbon::parse($from);
        $to = Carbon::parse($to);

        if ($from->gt($to)) {
            return response()->json(['error' => 'from date cannot be greater than to date'], 406);
        }

        $from = $from->format('Y-m-d 00:00:00');
        $to = $to->format('Y-m-d 23:59:59');

        return SmsLog::with('user')->whereBetween('created_at', [$from, $to])->paginate(10);
    }
}
