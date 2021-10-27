<?php

namespace App\Http\Controllers\Sms;

use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\CustomerGroup;
use App\Models\SmsCampaign;
use App\Models\SmsLog;
use App\Services\MessageService\MessagingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;

class SmsController extends Controller
{
    protected $phonesPerWorker;
    protected $messageService;

    public function __construct(MessagingService $messageService)
    {
        $this->messageService = $messageService;
        $this->phonesPerWorker = 200;
    }

    public function createCampaigns(Request $request)
    {
        $this->validateRequest($request, true);

        $request['phone_numbers'] = collect($request->group_slugs)->map(function ($groupSlug) {
            $customerGroup = CustomerGroup::where('slug', $groupSlug)->first();
            return $customerGroup->customers()->pluck('phone_number');
        })->collapse()->unique()->values()->toArray();

        $this->prepareAndSend($request);
        return response()->json(['Your sms is preparing and being sent to users.'], 200);
    }

    public function send(Request $request)
    {
        $this->validateRequest($request);
        $this->prepareAndSend($request);
        return response()->json(['Your sms is preparing and being sent to users.'], 200);
    }

    private function validateRequest($request, $campaign = false)
    {
        $rules = [
            'name' => 'nullable|string',
            'description' => 'nullable',
            'message' => 'required',
            'type' => 'required|in:otp,marketing',
        ];

        if ($campaign) {
            $rules['group_slugs'] = 'required|array';
            $rules['group_slugs.*'] = 'required|exists:App\Models\CustomerGroup,slug';
        } else {
            $rules['phone_numbers'] = 'required|array|max:10000';
        }

        $request->validate($rules);
    }

    private function prepareAndSend($request)
    {
        $userId = Auth::guard('users')->user()->id;
        $message = trim(SmsHelper::removeEmoji($request->message));
        $smsData = SmsHelper::prepareSmsData($message, $userId);
        $workerCount = $this->calculateWorkerCount($request->phone_numbers);
        $this->createSmsCampaign($request, $smsData['batchId']);

        for ($i = 0; $i < $workerCount; $i++) {
            $uniqueKey = StringHelper::generateUniqueSlug();
            $phoneNumbers = array_slice($request->phone_numbers, $i * $this->phonesPerWorker, $this->phonesPerWorker);
            SendSms::dispatch($uniqueKey, $phoneNumbers, $message, $request->type, $smsData, $this->messageService);
        }
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
            'name' => $request->name ? $request->name : 'Instant Message',
            'description' => $request->description,
            'type' => $request->type,
            'total_numbers' => count($request->phone_numbers),
            'sent_at' => Carbon::now(),
        ]);
    }

    public function getSmsCampaigns()
    {
        return SmsCampaign::paginate(10);
    }

    public function getLogs(Request $request)
    {
        $log = SmsLog::with('user');
        if ($request->status) {
            $log = $log->whereIn('status', $request->status);
        }
        if ($request->type) {
            $log = $log->whereIn('type', $request->type);
        }
        if ($request->phone_number) {
            $log = $log->whereIn('phone_number', $request->phone_number);
        }
        if ($request->from && $request->to) {
            $from = Carbon::parse($request->from);
            $to = Carbon::parse($request->to);

            if ($from->gt($to)) {
                $temp = $to;
                $to = $from;
                $from = $temp;
            }

            $from = $from->format('Y-m-d 00:00:00');
            $to = $to->format('Y-m-d 23:59:59');
            $log = $log->whereBetween('created_at', [$from, $to]);
        }
        return $log->orderBy('created_at', 'desc')->paginate(10);
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
