<?php

namespace App\Repositories\Shop\ShopCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShopCategoryUpdateRequest extends FormRequest
{
    private $shopCategoryRepository;

    public function __construct(ShopCategoryRepositoryInterface $shopCategoryRepository)
    {
        $this->shopCategoryRepository = $shopCategoryRepository;
    }

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
            'code' => 'required|size:3',
            'name' => [
                'required',
                Rule::unique('shop_categories')->ignore($this->route('shopCategory'), 'slug'),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'shop_main_category_slug' => 'nullable|exists:App\Models\ShopMainCategory,slug',
        ];
    }

    /**
     * Get the validated data from the request.
     *
     * @return array
     */
    public function validated()
    {
        if (request('shop_main_category_slug')) {
            return array_merge(parent::validated(), [
                'shop_main_category_id' => $this->shopCategoryRepository->getMainCategoryIdBySlug(request('shop_main_category_slug')),
            ]);
        }

        return parent::validated();
    }
}
