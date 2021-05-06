<?php

namespace App\Jobs;

use App\Helpers\SmsHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;

class SendSms implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uniqueKey;
    protected $phoneNumbers;
    protected $message;
    protected $type;
    protected $smsData;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uniqueKey, $phoneNumbers, $message, $type, $smsData)
    {
        $this->uniqueKey = $uniqueKey;
        $this->phoneNumbers = $phoneNumbers;
        $this->message = $message;
        $this->type = $type;
        $this->smsData = $smsData;
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->uniqueKey;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->phoneNumbers as $number) {
            $validator = $this->validateNumber($number);

            if (!$validator->fails()) {
                $phoneNumber = PhoneNumber::make($number, 'MM');

                try {
                    $smsResponse = SmsHelper::sendSms($phoneNumber, $this->message);
                    $status = 'Success';

                    if ($smsResponse['status'] !== 0) {
                        $status = 'Failed';
                    }

                    SmsHelper::storeSmsLog($this->smsData, $smsResponse, $phoneNumber, $this->type, $status);

                } catch (\Exception $e) {
                    Log::critical($e);
                    SmsHelper::storeSmsLog($this->smsData, null, $phoneNumber, $this->type, 'Error');
                }
            } else {
                SmsHelper::storeSmsLog($this->smsData, null, $number, $this->type, 'Rejected');
            }
        }
    }

    private function validateNumber($number)
    {
        return Validator::make(['number' => $number], [
            'number' => 'phone:MM',
        ]);
    }
}