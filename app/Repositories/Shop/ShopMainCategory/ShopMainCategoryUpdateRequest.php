<?php

namespace App\Repositories\Shop\ShopMainCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShopMainCategoryUpdateRequest extends FormRequest
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
            'code' => [
                'required',
                Rule::unique('shop_main_categories')->ignore($this->route('shopMainCategory'), 'slug'),
                'size:2',
            ],
            'name' => [
                'required',
                Rule::unique('shop_main_categories')->ignore($this->route('shopMainCategory'), 'slug'),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ];
    }
}
