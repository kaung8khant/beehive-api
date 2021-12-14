<?php

namespace App\Exports;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;
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
        return ProductVariant::query()->with("product");
    }

    public function stringifyVariant($variants)
    {
        $implode = array();
        foreach ($variants as $variant) {
            $implode[] = implode(':', $variant);
        }
        return implode(' / ', $implode);
    }

    /**
     * @var Product $product
     */
    public function map($productVatiant): array
    {
        return [
            $productVatiant->product->code,
            $productVatiant->slug,
            $productVatiant->product->name,
            $productVatiant->product->description,
            $productVatiant->product->is_enable ? '1' : '0',
            $this->stringifyVariant($productVatiant->variant),
            $productVatiant->price ? $productVatiant->price : '0',
            $productVatiant->vendor_price ? $productVatiant->vendor_price : '0',
            $productVatiant->tax ? $productVatiant->tax : '0',
            $productVatiant->discount ? $productVatiant->discount : '0',
            $productVatiant->is_enable ? '1' : '0',
            Shop::where('id', $productVatiant->product->shop_id)->value('name'),
                Shop::where('id', $productVatiant->product->shop_id)->value('slug'),
                ShopCategory::where('id', $productVatiant->product->shop_category_id)->value('name'),
                ShopCategory::where('id', $productVatiant->product->shop_category_id)->value('code'),
                ShopSubCategory::where('id', $productVatiant->product->shop_sub_category_id)->value('name'),
                ShopSubCategory::where('id', $productVatiant->product->shop_sub_category_id)->value('code'),
                Brand::where('id', $productVatiant->product->brand_id)->value('name'),
                Brand::where('id', $productVatiant->product->brand_id)->value('code'),
        ];
    }

    public function headings(): array
    {
        return [
            'code',
            'product_variant_slug',
            'name',
            'description',
            'is_enable',
            'variant',
            'price',
            'vendor_price',
            'tax',
            'discount',
            'variant_is_enable',
            'shop',
            'shop_slug',
            'shop_category',
            'shop_category_code',
            'shop_sub_category',
            'shop_sub_category_code',
            'brand',
            'brand_code',
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
            'P' => ['alignment' => ['horizontal' => 'center']],
            'Q' => ['alignment' => ['horizontal' => 'center']],
            'R' => ['alignment' => ['horizontal' => 'center']],
            'S' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 30,
            'D' => 30,
            'E' => 10,
            'F' => 30,
            'G' => 20,
            'H' => 10,
            'I' => 25,
            'J' => 25,
            'K' => 25,
            'L' => 25,
            'M' => 25,
            'N' => 25,
            'O' => 25,
            'P' => 25,
            'Q' => 25,
            'R' => 25,
            'S' => 25,
            'T' => 25,
        ];
    }
}
