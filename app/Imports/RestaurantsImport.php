<?php

namespace App\Imports;

use App\Exceptions\ImportException;
use App\Helpers\StringHelper;
use App\Jobs\ImportRestaurant;
use App\Models\Restaurant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RestaurantsImport implements ToCollection, WithHeadingRow
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
            ImportRestaurant::dispatch($uniqueKey, $rowsBatch);
        }
    }

    private function validate($rows)
    {
        $validatorErrors = [];

        foreach ($rows as $key => $row) {
            $validateRow = $row->toArray();

            if (isset($row['id'])) {
                $restaurant = Restaurant::where('slug', $row['id'])->first();

                $rules = [
                    'name' => [
                        'required',
                        Rule::unique('restaurants')->ignore($restaurant->id),
                    ],
                    'is_enable' => ['required', 'boolean'],
                ];

                $validator = Validator::make(
                    $validateRow,
                    $rules
                );
            } else {
                $rules = [
                    'name' => [
                        'required',
                        'unique:restaurants',
                    ],
                    'is_enable' => ['required', 'boolean'],
                    'branch_name' => ['required'],
                    'branch_address' => ['nullable'],
                    'branch_contact_number' => ['required', 'phone:MM'],
                    'branch_opening_time' => ['required', 'date_format:H:i'],
                    'branch_closing_time' => ['required', 'date_format:H:i'],
                    'branch_latitude' => ['required', 'numeric'],
                    'branch_longitude' => ['required', 'numeric'],
                    'branch_township' => ['nullable', 'string'],
                    'branch_city' => ['nullable', 'string'],
                ];

                $validator = Validator::make(
                    $validateRow,
                    $rules,
                    [
                        'branch_contact_number.phone' => 'Invalid phone number.',
                    ]
                );
            }

            if ($validator->fails()) {
                if (isset($row['id'])) {
                    $validatorErrors[] = [
                        'row' => $key + 2,
                        'name' => $row['name'],
                        'is_enable' => $row['is_enable'],
                        'errors' => $validator->errors(),
                    ];
                } else {
                    $validatorErrors[] = [
                        'row' => $key + 2,
                        'name' => $row['name'],
                        'is_enable' => $row['is_enable'],
                        'branch_name' => $row['branch_name'],
                        'contact_number' => $row['branch_contact_number'],
                        'opening_time' => $row['branch_opening_time'],
                        'closing_time' => $row['branch_closing_time'],
                        'latitude' => $row['branch_latitude'],
                        'longitude' => $row['branch_longitude'],
                        'township' => $row['branch_township'],
                        'city' => $row['branch_city'],
                        'errors' => $validator->errors(),
                    ];
                }
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
