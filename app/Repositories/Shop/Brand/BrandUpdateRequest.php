<?php

namespace App\Repositories\Shop\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandUpdateRequest extends FormRequest
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
                'size:4',
            ],
            'name' => [
                'required',
                Rule::unique('brands')->ignore($this->route('brand'), 'slug'),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ];
    }
}
