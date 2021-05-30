<?php

namespace App\Imports;

use App\Exceptions\ImportException;
use App\Helpers\StringHelper;
use App\Jobs\ImportCustomer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToCollection, WithHeadingRow
{
    protected $batchPerWorker;

    public function __construct()
    {
        $this->batchPerWorker = 500;
    }

    public function collection(Collection $rows)
    {
        // $this->validate($rows);

        $rows = $rows->toArray();
        $workerCount = $this->calculateWorkerCount($rows);

        for ($i = 0; $i < $workerCount; $i++) {
            $uniqueKey = StringHelper::generateUniqueSlug();
            $rowsBatch = array_slice($rows, $i * $this->batchPerWorker, $this->batchPerWorker);
            ImportCustomer::dispatch($uniqueKey, $rowsBatch);
        }
    }

    private function validate($rows)
    {
        $validatorErrors = [];

        foreach ($rows as $key => $row) {
            $validateRow = $row->toArray();

            if (isset($row['phone_number'])) {
                $validateRow['phone_number'] = str_replace([' ', '-'], '', $row['phone_number']);
            }

            $validator = Validator::make(
                $validateRow,
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

            if ($validator->fails()) {
                $validatorErrors[] = [
                    'row' => $key + 2,
                    'name' => $row['name'],
                    'phone_number' => $row['phone_number'],
                    'errors' => $validator->errors(),
                ];
            }
        }

        if (count($validatorErrors) > 0) {
            throw new ImportException(json_encode($validatorErrors));
        }
    }

    private function calculateWorkerCount($rows)
    {
        $rowCount = count($rows);
        $workerCount = intval($rowCount / $this->batchPerWorker);

        if ($rowCount % $this->batchPerWorker !== 0) {
            $workerCount += 1;
        }

        return $workerCount;
    }
}
