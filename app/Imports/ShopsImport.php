<?php

namespace App\Imports;

use App\Exceptions\ImportException;
use App\Helpers\StringHelper;
use App\Jobs\ImportShop;
use App\Models\Shop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ShopsImport implements ToCollection, WithHeadingRow
{
    protected $batchPerWorker;

    public function __construct()
    {
        $this->batchPerWorker = 200;
    }

    public function collection(Collection $rows)
    {
        $this->validate($rows);

        $rows = $rows->toArray();
        $workerCount = $this->calculateWorkerCount($rows);

        for ($i = 0; $i < $workerCount; $i++) {
            $uniqueKey = StringHelper::generateUniqueSlug();
            $rowsBatch = array_slice($rows, $i * $this->batchPerWorker, $this->batchPerWorker);
            ImportShop::dispatch($uniqueKey, $rowsBatch);
        }
    }

    private function validate($rows)
    {
        $validatorErrors = [];

        foreach ($rows as $key => $row) {
            $validateRow = $row->toArray();

            $rules = [
                'name' => ['required', 'unique:shops'],
                'is_enable' => ['required', 'boolean'],
                'is_official' => ['required', 'boolean'],
                'address' => ['nullable'],
                'contact_number' => ['required', 'phone:MM'],
                'opening_time' => ['required', 'date_format:H:i'],
                'closing_time' => ['required', 'date_format:H:i'],
                'latitude' => ['required', 'numeric'],
                'longitude' => ['required', 'numeric'],
                'township_slug' => ['nullable', 'exists:App\Models\Township,slug'],
            ];

            if (isset($row['id'])) {
                $shop = Shop::where('slug', $row['id'])->first();
                $rules['name'][1] = Rule::unique('shops')->ignore($shop->id);
            }

            $validator = Validator::make(
                $validateRow,
                $rules,
                [
                    'contact_number.phone' => 'Invalid phone number.',
                ]
            );

            if ($validator->fails()) {
                $validatorErrors[] = [
                    'row' => $key + 2,
                    'name' => $row['name'],
                    'is_enable' => $row['is_enable'],
                    'is_official' => $row['is_official'],
                    'contact_number' => $row['contact_number'],
                    'opening_time' => $row['opening_time'],
                    'closing_time' => $row['closing_time'],
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'township_slug' => $row['township_slug'],
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
