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
    public function __construct(string $params)
    {
        $this->params = $params;
    }

    public function query()
    {
        $shop = Shop::where('slug', $this->params)->firstOrFail();
        // $products = Product::query()->where('shop_id', $shop->id);

        return  ProductVariant::with("product")->whereHas('product', function ($q) use ($shop) {
            $q->where('shop_id', $shop->id);
        });
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
            $productVatiant->product->slug,
            $productVatiant->slug,
            $productVatiant->product->name,
            $productVatiant->product->description,
            $this->stringifyVariant($productVatiant->variant),
            $productVatiant->price ? $productVatiant->price : '0',
            $productVatiant->vendor_price ? $productVatiant->vendor_price : '0',
            $productVatiant->tax ? $productVatiant->tax : '0',
            $productVatiant->discount ? $productVatiant->discount : '0',
            $productVatiant->is_enable ? '1' : '0',
            Shop::where('id', $productVatiant->product->shop_id)->value('name'),
                Shop::where('id', $productVatiant->product->shop_id)->value('slug'),
                ShopCategory::where('id', $productVatiant->product->shop_category_id)->value('name'),
                ShopCategory::where('id', $productVatiant->product->shop_category_id)->value('slug'),
                ShopSubCategory::where('id', $productVatiant->product->shop_sub_category_id)->value('name'),
                ShopSubCategory::where('id', $productVatiant->product->shop_sub_category_id)->value('slug'),
                Brand::where('id', $productVatiant->product->brand_id)->value('name'),
                Brand::where('id', $productVatiant->product->brand_id)->value('slug'),
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'product_variant_slug',
            'name',
            'description',
            'variant',
            'price',
            'vendor_price',
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
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 45,
            'D' => 45,
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
            'P' => 25,
            'Q' => 25,
            'R' => 25,
        ];
    }
}
