<?php

namespace App\Repositories\Shop\ShopMainCategory;

use App\Helpers\StringHelper;
use Illuminate\Foundation\Http\FormRequest;

class ShopMainCategoryCreateRequest extends FormRequest
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
            'code' => 'required|unique:shop_main_categories|size:2',
            'slug' => 'required|unique:shop_main_categories',
            'name' => 'required|unique:shop_main_categories',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
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
            'slug' => StringHelper::generateUniqueSlugWithTable('shop_main_categories'),
        ]);
    }
}
