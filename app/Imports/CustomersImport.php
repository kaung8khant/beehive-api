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
use Maatwebsite\Excel\Concerns\WithValidation;

class CustomersImport implements ToModel, WithHeadingRow, WithChunkReading, WithUpserts, WithValidation
{
    use Importable;

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
            'slug' => isset($row['slug']) ? $row['slug'] : StringHelper::generateUniqueSlug(),
            'name' => $row['name'],
            'email' => $row['email'],
            'phone_number' => PhoneNumber::make($row['phone_number'], 'MM'),
            'password' => $row['password'] ? $row['password'] : Hash::make(StringHelper::generateRandomPassword()),
            'gender' => $row['gender'],
            'created_by' => 'admin',
        ];
        $customer = Customer::create($newCustomer);

        if (isset($row['customer_group_name'])) {
            if (CustomerGroup::where('name', $row['customer_group_name'])->exists()) {
                $customerGroupId = CustomerGroup::where('name', $row['customer_group_name'])->first()->id;
                $customer->customerGroups()->attach($customerGroupId);
            } else {
                $group = CustomerGroup::create([
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['customer_group_name'],
                ]);
                $customer->customerGroups()->attach($group->id);
            }
        }
        return new Customer($newCustomer);
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
            'name' => 'required|max:255',
            'phone_number' => 'required|phone:MM|unique:customers',
            'password' => 'nullable|string|min:6',
            'customer_group_name' => 'nullable|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'phone_number.phone' => 'Invalid Phone Number',
        ];
    }
}
