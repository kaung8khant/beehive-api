<?php

namespace App\Repositories\Driver;

use Illuminate\Foundation\Http\FormRequest;

class DriverUpdateRequest extends FormRequest
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
        $driver = $this->route('driver');

        return [
            'username' => 'required|unique:users,username,' . $driver->id,
            'name' => 'required|string',
            'phone_number' => 'required|phone:MM|unique:users,phone_number,' . $driver->id,
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ];
    }

    public function messages()
    {
        return [
            'phone_number.phone' => 'Invalid phone number.'
        ];
    }
}
