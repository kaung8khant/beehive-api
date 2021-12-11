<?php

namespace App\Repositories\Shop\ShopCategory;

use App\Helpers\StringHelper;
use Illuminate\Foundation\Http\FormRequest;

class ShopCategoryCreateRequest extends FormRequest
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
            'slug' => 'required|unique:shop_categories',
            'name' => 'required',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'shop_main_category_slug' => 'nullable|exists:App\Models\ShopMainCategory,slug',
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
            'slug' => StringHelper::generateUniqueSlugWithTable('shop_categories'),
        ]);
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
