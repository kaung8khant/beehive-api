<?php

namespace App\Repositories\Shop\ShopOrder;

use App\Helpers\StringHelper;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class ShopOrderCreateRequest extends FormRequest
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
            'slug' => 'required|unique:shop_orders',
            'order_date' => 'nullable',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay,Credit',
            'delivery_mode' => 'required|in:pickup,delivery',
            'promo_code' => 'nullable|string|exists:App\Models\Promocode,code',
            'customer_slug' => 'required|string|exists:App\Models\Customer,slug',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'address' => 'required',
            'address.house_number' => 'nullable|string',
            'address.floor' => 'nullable|integer|min:0|max:50',
            'address.street_name' => 'nullable|string',
            'address.latitude' => 'nullable|numeric',
            'address.longitude' => 'nullable|numeric',
            'delivery_fee' => 'nullable|numeric',
            'order_items' => 'required|array',
            'order_items.*.slug' => 'required|string|exists:App\Models\Product,slug',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.variant_slug' => 'required|exists:App\Models\ProductVariant,slug',
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
            'slug' => StringHelper::generateUniqueSlugWithTable('shop_orders'),
        ]);
    }

    /**
     * Get the validated data from the request.
     *
     * @return array
     */
    public function validated()
    {
        $validated = parent::validated();
        $validated['customer_id'] = Customer::where('slug', $validated['customer_slug'])->first()->id;
        return $validated;
    }
}
