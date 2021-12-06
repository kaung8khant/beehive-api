<?php

namespace App\Repositories\Shop\Brand;

use App\Helpers\StringHelper;
use Illuminate\Foundation\Http\FormRequest;

class BrandCreateRequest extends FormRequest
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
            'code' => 'required|unique:brands|size:4',
            'slug' => 'required|unique:brands',
            'name' => 'required|unique:brands',
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
            'slug' => StringHelper::generateUniqueSlugWithTable('brands'),
        ]);
    }
}
