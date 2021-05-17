<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Helpers\StringHelper;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Illuminate\Support\Facades\Hash;

class CustomersImport implements ToModel, WithHeadingRow, WithChunkReading, WithUpserts
{
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
        return new Customer([
            'slug' => isset($row['slug']) ? $row['slug'] : StringHelper::generateUniqueSlug(),
            'name' => $row['name'],
            'email' => $row['email'],
            'phone_number' =>PhoneNumber::make($row['phone_number'], 'MM'),
            'password' => $row['password'] ? $row['password'] : Hash::make(StringHelper::generateRandomPassword()),
            'gender' => $row['gender'],
            'created_by' => 'admin',
        ]);
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
            'gender' => 'nullable|in:Male,Female',
            'phone_number.phone' => 'Invalid phone number.',
        ];
    }
}
