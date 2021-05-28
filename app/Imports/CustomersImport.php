<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Helpers\StringHelper;
use App\Models\CustomerGroup;
use Propaganistas\LaravelPhone\PhoneNumber;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;

class CustomersImport implements ToModel, WithHeadingRow, WithChunkReading, WithUpserts, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public function __construct()
    {
        ini_set('memory_limit', '256M');
    }
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $newCustomer = [
            'id' => isset($row['id']) && $this->transformSlugToId($row['id']),
            'slug' => isset($row['id']) ? $row['id'] : StringHelper::generateUniqueSlug(),
            'name' => $row['name'] ? $row['name'] : 'Unknown Customer',
            'email' => $row['email'],
            'phone_number' => PhoneNumber::make($row['phone_number'], 'MM'),
            'password' => Hash::make(StringHelper::generateRandomPassword()),
            'gender' => $row['gender'],
            'created_by' => 'admin',
        ];


        $customer = Customer::where('phone_number', $newCustomer['phone_number'])->first();

        if (!$customer) {
            $customer = Customer::create($newCustomer);
        }

        if (isset($row['customer_group_name'])) {
            if (CustomerGroup::where('name', $row['customer_group_name'])->exists()) {
                $customerGroupId = CustomerGroup::where('name', $row['customer_group_name'])->first()->id;
            } else {
                $customerGroupId = CustomerGroup::create([
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['customer_group_name'],
                ])->id;
            }
            if (!$customer->customerGroups()->where('customer_group_id', $customerGroupId)->exists()) {
                $customer->customerGroups()->attach($customerGroupId);
            }
        }
        return null;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * @return string|array
     */
    public function uniqueBy()
    {
        return 'slug';
    }

    public function rules(): array
    {
        return [
            'email' => 'nullable|email|unique:customers',
            'name' => 'nullable|max:255',
            'phone_number' => 'required|phone:MM',
            'customer_group_name' => 'nullable|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'phone_number.phone' => 'Invalid Phone Number',
        ];
    }

    public function transformSlugToId($value)
    {
        $customer = Customer::where('slug', $value)->first();

        if (!$customer) {
            return null;
        }

        return $customer->id;
    }

    // /**
    //  * @param Failure[] $failures
    //  */
    // public function onFailure(Failure ...$failures)
    // {
    //     // Handle the failures how you'd like.
    // }
}
