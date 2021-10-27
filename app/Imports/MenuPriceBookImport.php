<?php

namespace App\Imports;

use App\Exceptions\ImportException;
use App\Helpers\StringHelper;
use App\Jobs\ImportMenuPriceBook;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MenuPriceBookImport implements ToCollection, WithHeadingRow
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
            ImportMenuPriceBook::dispatch($uniqueKey, $rowsBatch, Auth::guard('users')->user()->id);
        }
    }

    private function validate($rows)
    {
        $validatorErrors = [];

        foreach ($rows as $key => $row) {
            $validateRow = $row->toArray();

            $rules = [
                'variant_slug' => 'required|exists:App\Models\MenuVariant,slug',
                'price' => 'required|numeric|max:99999999',
            ];

            $validator = Validator::make(
                $validateRow,
                $rules
            );

            if ($validator->fails()) {
                $validatorErrors[] = [
                    'row' => $key + 2,
                    'variant_slug' => $row['variant_slug'],
                    'price' => $row['price'],
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
