<?php

namespace App\Imports;

use App\Exceptions\ImportException;
use App\Helpers\StringHelper;
use App\Jobs\ImportProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
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
            ImportProduct::dispatch($uniqueKey, $rowsBatch, Auth::guard('users')->user()->id);
        }
    }

    private function validate($rows)
    {
        $validatorErrors = [];

        foreach ($rows as $key => $row) {
            $validateRow = $row->toArray();

            $rules = [
                'name' => 'required',
                'description' => 'nullable|string',
                'price' => 'required|numeric|max:99999999',
                'vendor_price' => 'required|numeric|max:99999999',
                'tax' => 'required|numeric',
                'discount' => 'required|numeric',
                'is_enable' => 'required|boolean',
                'shop_slug' => 'required|exists:App\Models\Shop,slug',
                'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
                'shop_sub_category_slug' => 'nullable|exists:App\Models\ShopSubCategory,slug',
                'brand_slug' => 'nullable|exists:App\Models\Brand,slug',
            ];

            $validator = Validator::make(
                $validateRow,
                $rules
            );

            if ($validator->fails()) {
                $validatorErrors[] = [
                    'row' => $key + 2,
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'vendor_price' => $row['vendor_price'],
                    'tax' => $row['tax'],
                    'discount' => $row['discount'],
                    'is_enable' => $row['is_enable'],
                    'shop_slug' => $row['shop_slug'],
                    'shop_category_slug' => $row['shop_category_slug'],
                    'shop_sub_category_slug' => $row['shop_sub_category_slug'],
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
