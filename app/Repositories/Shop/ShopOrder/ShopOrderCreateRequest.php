<?php

namespace App\Repositories\Shop\ShopOrder;

use App\Helpers\ShopOrderHelper;
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
        return ShopOrderHelper::getRules(true);
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
