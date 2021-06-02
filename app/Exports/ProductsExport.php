<?php

namespace App\Exports;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\ShopSubCategory;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return Product::query();
    }

    /**
     * @var Product $product
     */
    public function map($product): array
    {
        return [
            $product->slug,
            $product->name,
            $product->description,
            $product->price ? $product->price : '0',
            $product->tax ? $product->tax : '0',
            $product->discount ? $product->discount : '0',
            $product->is_enable ? '1' : '0',
            Shop::where('id', $product->shop_id)->value('name'),
            Shop::where('id', $product->shop_id)->value('slug'),
            ShopCategory::where('id', $product->shop_category_id)->value('name'),
            ShopCategory::where('id', $product->shop_category_id)->value('slug'),
            ShopSubCategory::where('id', $product->shop_sub_category_id)->value('name'),
            ShopSubCategory::where('id', $product->shop_sub_category_id)->value('slug'),
            Brand::where('id', $product->brand_id)->value('name'),
            Brand::where('id', $product->brand_id)->value('slug'),
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'description',
            'price',
            'tax',
            'discount',
            'is_enable',
            'shop',
            'shop_slug',
            'shop_category',
            'shop_category_slug',
            'shop_sub_category',
            'shop_sub_category_slug',
            'brand',
            'brand_slug',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
            'A' => ['alignment' => ['horizontal' => 'center']],
            'B' => ['alignment' => ['horizontal' => 'center']],
            'C' => ['alignment' => ['horizontal' => 'center']],
            'D' => ['alignment' => ['horizontal' => 'center']],
            'E' => ['alignment' => ['horizontal' => 'center']],
            'F' => ['alignment' => ['horizontal' => 'center']],
            'G' => ['alignment' => ['horizontal' => 'center']],
            'H' => ['alignment' => ['horizontal' => 'center']],
            'I' => ['alignment' => ['horizontal' => 'center']],
            'J' => ['alignment' => ['horizontal' => 'center']],
            'K' => ['alignment' => ['horizontal' => 'center']],
            'L' => ['alignment' => ['horizontal' => 'center']],
            'M' => ['alignment' => ['horizontal' => 'center']],
            'N' => ['alignment' => ['horizontal' => 'center']],
            'O' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 45,
            'D' => 10,
            'E' => 10,
            'F' => 10,
            'G' => 10,
            'H' => 25,
            'I' => 25,
            'J' => 25,
            'K' => 25,
            'L' => 25,
            'M' => 25,
            'N' => 25,
            'O' => 25,
        ];
    }
}
