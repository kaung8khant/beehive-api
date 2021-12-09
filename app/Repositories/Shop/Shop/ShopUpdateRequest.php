<?php

namespace App\Repositories\Shop\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class ShopUpdateRequest extends FormRequest
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
            'name' => [
                'required',
                Rule::unique('shops')->ignore($this->route('shop'), 'slug'),
            ],
            'address' => 'nullable',
            'city' => 'nullable|string',
            'township' => 'nullable|string',
            'contact_number' => 'required|phone:MM',
            'notify_numbers' => 'nullable|array',
            'notify_numbers.*' => 'required|phone:MM',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'is_official' => 'required|boolean',
            'is_enable' => 'required|boolean',
            'shop_tags' => 'nullable|array',
            'shop_tags.*' => 'exists:App\Models\ShopTag,slug',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'cover_slugs' => 'nullable|array',
            'cover_slugs.*' => 'nullable|exists:App\Models\File,slug',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'contact_number.phone' => 'Invalid phone number.',
            'notify_numbers.*.phone' => 'Invalid phone number.',
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
        $validated['contact_number'] = PhoneNumber::make(request('contact_number'), 'MM');

        if (request('notify_numbers')) {
            $validated['notify_numbers'] = $this->makeNotifyNumbers(request('notify_numbers'));
        }

        return $validated;
    }

    private function makeNotifyNumbers($notifyNumbers)
    {
        $notifyNumbers = array_map(function ($notifyNumber) {
            return PhoneNumber::make($notifyNumber, 'MM');
        }, $notifyNumbers);

        return array_values(array_unique($notifyNumbers));
    }
}
