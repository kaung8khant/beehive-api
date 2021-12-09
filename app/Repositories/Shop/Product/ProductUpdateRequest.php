<?php

namespace App\Repositories\Shop\Product;

use App\Helpers\CacheHelper;
use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
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
        ];
    }

    /**
     * Get the validated data from the request.
     *
     * @return array
     */
    public function validated()
    {
        $validated = parent::validated();

        $validated['shop_id'] = CacheHelper::getShopIdBySlug($validated['shop_slug']);
        $validated['shop_category_id'] = CacheHelper::getShopCategoryIdBySlug($validated['shop_category_slug']);

        if (isset($validated['shop_sub_category_slug'])) {
            $validated['shop_sub_category_id'] = CacheHelper::getShopSubCategoryIdBySlug($validated['shop_sub_category_slug']);
        }

        if (isset($validated['brand_slug'])) {
            $validated['brand_id'] = CacheHelper::getBrandIdBySlug($validated['brand_slug']);
        }

        return $validated;
    }
}
