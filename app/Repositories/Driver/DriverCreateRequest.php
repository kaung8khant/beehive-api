<?php

namespace App\Repositories\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;


class DriverCreateRequest extends FormRequest
{
    use StringHelper;
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
        return
            [
                'slug' => 'required|unique:users',
                'username' => 'required|unique:users',
                'name' => 'required|string',
                'phone_number' => 'required|phone:MM|unique:users',
                'password' => 'required|min:6',
                'image_slug' => 'nullable|exists:App\Models\File,slug',
            ];
    }

    public function messages()
    {
        return [
            'phone_number.phone' => 'Invalid phone number.'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'slug' => StringHelper::generateUniqueSlug(),
        ]);
    }
}
