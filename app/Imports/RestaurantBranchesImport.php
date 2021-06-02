<?php

namespace App\Imports;

use App\Models\RestaurantBranch;
use App\Exceptions\ImportException;
use App\Helpers\StringHelper;
use App\Jobs\ImportRestaurantBranch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RestaurantBranchesImport implements ToCollection, WithHeadingRow
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
            ImportRestaurantBranch::dispatch($uniqueKey, $rowsBatch);
        }
    }

    private function validate($rows)
    {
        $validatorErrors = [];

        foreach ($rows as $key => $row) {
            $validateRow = $row->toArray();

            $rules = [
                'name' => ['required', 'unique:restaurant_branches'],
                'is_enable' => ['required','boolean'],
                'address' => ['nullable'],
                'contact_number' => ['required','phone:MM'],
                'opening_time' => ['required','date_format:H:i'],
                'closing_time' => ['required','date_format:H:i'],
                'latitude' => ['required','numeric'],
                'longitude' => ['required','numeric'],
                'township_slug' => ['nullable','exists:App\Models\Township,slug'],
                'restaurant_slug' => ['required','exists:App\Models\Restaurant,slug'],
            ];

            if (isset($row['id'])) {
                $restaurantBranch = RestaurantBranch::where('slug', $row['id'])->first();
                $rules['name'][1] = Rule::unique('restaurant_branches')->ignore($restaurantBranch->id);
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
                    'contact_number' => $row['contact_number'],
                    'opening_time' => $row['opening_time'],
                    'closing_time' => $row['closing_time'],
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'township_slug' => $row['township_slug'],
                    'restaurant_slug' => $row['restaurant_slug'],
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
