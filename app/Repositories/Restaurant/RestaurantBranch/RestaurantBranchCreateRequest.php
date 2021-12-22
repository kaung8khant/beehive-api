<?php

namespace App\Repositories\Restaurant\RestaurantBranch;

use App\Helpers\StringHelper;
use App\Models\Restaurant;
use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\PhoneNumber;

class RestaurantBranchCreateRequest extends FormRequest
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
            'slug' => 'required|unique:restaurant_branches',
            'name' => 'required',
            'address' => 'nullable',
            'city' => 'nullable|string',
            'township' => 'nullable|string',
            'contact_number' => 'required|phone:MM',
            'notify_numbers' => 'nullable|array',
            'notify_numbers.*' => 'required|phone:MM',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required',
            'longitude' => 'required',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'is_enable' => 'required|boolean',
            'free_delivery' => 'nullable|boolean',
            'pre_order' => 'nullable|boolean',
            'extra_charges' => 'nullable|array',
            'extra_charges.*.name' => 'required|string',
            'extra_charges.*.value' => 'required|numeric',
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
            'slug' => StringHelper::generateUniqueSlugWithTable('restaurant_branches'),
        ]);
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
        $validated['restaurant_id'] = Restaurant::where('slug', request('restaurant_slug'))->value('id');

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
