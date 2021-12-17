<?php

namespace App\Exports;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BrandProductsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct(string $params)
    {
        $this->params = $params;
    }

    public function query()
    {
        $brand = Brand::where('slug', $this->params)->firstOrFail();

        return ProductVariant::with(['product', 'product.shop', 'product.shopCategory', 'product.shopCategory.shopMainCategory', 'product.shopSubCategory', 'product.brand'])
            ->whereHas('product', fn ($q) => $q->where('brand_id', $brand->id));
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
            $productVatiant->product->shop->slug,
            $productVatiant->product->shop->name,
            $productVatiant->product->shopCategory->shopMainCategory ? $productVatiant->product->shopCategory->shopMainCategory->code : '',
            $productVatiant->product->shopCategory->shopMainCategory ? $productVatiant->product->shopCategory->shopMainCategory->name : '',
            $productVatiant->product->shopCategory->code,
            $productVatiant->product->shopCategory->name,
            $productVatiant->product->shopSubCategory ? $productVatiant->product->shopSubCategory->code : '',
            $productVatiant->product->shopSubCategory ? $productVatiant->product->shopSubCategory->name : '',
            $productVatiant->product->brand ? $productVatiant->product->brand->code : '',
            $productVatiant->product->brand ? $productVatiant->product->brand->name : '',
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
            'shop_slug',
            'shop',
            'product_type_code',
            'product_type',
            'shop_category_code',
            'shop_category',
            'shop_sub_category_code',
            'shop_sub_category',
            'brand_code',
            'brand',
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
            'T' => ['alignment' => ['horizontal' => 'center']],
            'U' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 30,
            'C' => 45,
            'D' => 50,
            'E' => 10,
            'F' => 20,
            'G' => 10,
            'H' => 25,
            'I' => 25,
            'J' => 25,
            'K' => 25,
            'L' => 25,
            'M' => 30,
            'N' => 25,
            'O' => 30,
            'P' => 25,
            'Q' => 30,
            'R' => 25,
            'S' => 30,
            'T' => 25,
            'U' => 30,
        ];
    }
}
