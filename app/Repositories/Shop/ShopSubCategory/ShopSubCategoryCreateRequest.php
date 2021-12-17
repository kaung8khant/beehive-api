<?php

namespace App\Repositories\Shop\ShopSubCategory;

use App\Helpers\StringHelper;
use Illuminate\Foundation\Http\FormRequest;

class ShopSubCategoryCreateRequest extends FormRequest
{
    private $subCategoryRepository;

    public function __construct(ShopSubCategoryRepositoryInterface $subCategoryRepository)
    {
        $this->subCategoryRepository = $subCategoryRepository;
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
            'code' => 'required|size:2',
            'slug' => 'required|unique:shop_sub_categories',
            'name' => 'required',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
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

    /**
     * Get the validated data from the request.
     *
     * @return array
     */
    public function validated()
    {
        return array_merge(parent::validated(), [
            'shop_category_id' => $this->subCategoryRepository->getShopCategoryIdBySlug(request('shop_category_slug')),
        ]);
    }
}
