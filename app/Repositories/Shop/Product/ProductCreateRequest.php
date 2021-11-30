<?php

namespace App\Repositories\Shop\Product;

use App\Helpers\StringHelper;
use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'slug' => 'required|unique:products',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'is_enable' => 'required|boolean',
            'shop_slug' => 'required|exists:App\Models\Shop,slug',
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
            'shop_sub_category_slug' => 'nullable|exists:App\Models\ShopSubCategory,slug',
            'brand_slug' => 'nullable|exists:App\Models\Brand,slug',
            'image_slugs' => 'nullable|array',
            'image_slugs.*' => 'nullable|exists:App\Models\File,slug',
            'cover_slugs' => 'nullable|array',
            'cover_slugs.*' => 'nullable|exists:App\Models\File,slug',

            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string',
            'variants.*.values' => 'required|array',

            'product_variants' => 'required_with:variants',
            'product_variants.*.variant' => 'required',
            'product_variants.*.price' => 'required|numeric',
            'product_variants.*.vendor_price' => 'required|numeric',
            'product_variants.*.tax' => 'required|numeric',
            'product_variants.*.discount' => 'required|numeric',
            'product_variants.*.is_enable' => 'required|boolean',
            'product_variants.*.image_slug' => 'nullable|exists:App\Models\File,slug',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'slug' => StringHelper::generateUniqueSlugWithTable('products'),
        ]);
    }
}
