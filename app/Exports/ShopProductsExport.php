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

class ShopProductsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    private $slug;

    public function __construct(string $slug)
    {
        $this->slug = $slug;
    }

    public function query()
    {
        $shop = Shop::where('slug', $this->slug)->firstOrFail();

        return ProductVariant::with(['product', 'product.shop', 'product.shopCategory', 'product.shopSubCategory', 'product.brand'])
            ->whereHas('product', fn($query) => $query->where('shop_id', $shop->id));
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
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 45,
            'D' => 45,
            'E' => 10,
            'F' => 30,
            'G' => 20,
            'H' => 20,
            'I' => 15,
            'J' => 15,
            'K' => 20,
            'L' => 20,
            'M' => 25,
            'N' => 25,
            'O' => 25,
            'P' => 25,
            'Q' => 25,
            'R' => 25,
            'S' => 25,
        ];
    }

    public function stringifyVariant($variants)
    {
        $implode = array();

        foreach ($variants as $variant) {
            $implode[] = implode(':', $variant);
        }

        return implode(' / ', $implode);
    }
}
