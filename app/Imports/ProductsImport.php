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
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|max:99999999',
                'vendor_price' => 'required|numeric|max:99999999',
                'tax' => 'required|numeric',
                'discount' => 'required|numeric',
                'is_enable' => 'required|boolean',
                'shop_slug' => 'required|exists:App\Models\Shop,slug',
                'product_type_code' => 'required|exists:App\Models\ShopMainCategory,code',
                'shop_category_code' => 'required|exists:App\Models\ShopCategory,code',
                'shop_sub_category_code' => 'nullable|exists:App\Models\ShopSubCategory,code',
                'brand_code' => 'nullable|exists:App\Models\Brand,code',
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
                    'product_type_code' => $row['product_type_code'],
                    'shop_category_code' => $row['shop_category_code'],
                    'shop_sub_category_code' => $row['shop_sub_category_code'],
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
