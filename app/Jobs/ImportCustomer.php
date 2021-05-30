<?php

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;

class ImportCustomer implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uniqueKey;
    protected $rows;

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
    public function __construct($uniqueKey, $rows)
    {
        $this->uniqueKey = $uniqueKey;
        $this->rows = $rows;
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
        foreach ($this->rows as $key => $row) {
            if (isset($row['phone_number'])) {
                $row['phone_number'] = str_replace([' ', '-'], '', $row['phone_number']);
            }

            $validator = Validator::make(
                $row,
                [
                    'name' => 'nullable|max:255',
                    'phone_number' => 'required|phone:MM',
                    'email' => 'nullable|email|unique:customers',
                    'customer_group_name' => 'nullable|string',
                ],
                [
                    'phone_number.phone' => 'Invalid phone number.',
                ]
            );

            if (!$validator->fails()) {
                $phoneNumber = PhoneNumber::make($row['phone_number'], 'MM');
                $customer = Customer::where('phone_number', $phoneNumber)->first();

                $customerData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['name'] ? $row['name'] : 'Unknown Customer',
                    'email' => $row['email'],
                    'phone_number' => $phoneNumber,
                    'password' => Hash::make(StringHelper::generateRandomPassword()),
                    'gender' => $row['gender'],
                    'created_by' => 'admin',
                ];

                if (!$customer) {
                    $customer = Customer::create($customerData);
                } else {
                    $customerData['slug'] = $customer->slug;
                    $customer->update($customerData);
                }

                if (isset($row['customer_group_name'])) {
                    $customerGroup = CustomerGroup::where('name', $row['customer_group_name'])->first();

                    if (!$customerGroup) {
                        $customerGroup = CustomerGroup::create([
                            'slug' => StringHelper::generateUniqueSlug(),
                            'name' => $row['customer_group_name'],
                        ]);
                    }

                    if (!$customer->customerGroups()->where('customer_group_id', $customerGroup->id)->exists()) {
                        // $customer->customerGroups()->detach($customerGroup->id);
                        $customer->customerGroups()->attach($customerGroup->id);
                    }
                }
            }
        }
    }
}
