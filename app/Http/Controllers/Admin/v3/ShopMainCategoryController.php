<?php

namespace App\Http\Controllers\Admin\v3;

use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\ShopMainCategory\ShopMainCategoryRepositoryInterface;
use Illuminate\Validation\Rule;

class ShopMainCategoryController extends Controller
{
    private $mainCategoryRepository;

    public function __construct(ShopMainCategoryRepositoryInterface $mainCategoryRepository)
    {
        $this->mainCategoryRepository = $mainCategoryRepository;
    }

    public function index()
    {
        return $this->mainCategoryRepository->all();
    }

    public function store()
    {
        $mainCategory = $this->mainCategoryRepository->create(self::validateCreate());
        return response()->json($mainCategory, 201);
    }

    public function show($slug)
    {
        return $this->mainCategoryRepository->find($slug);
    }

    public function update($slug)
    {
        return $this->mainCategoryRepository->update($slug, self::validateUpdate($slug));
    }

    public function destroy($slug)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        $this->mainCategoryRepository->delete($slug);
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function updateSearchIndex($slug)
    {
        return $this->mainCategoryRepository->update($slug, request()->validate([
            'search_index' => 'required|numeric',
        ]));
    }

    private static function validateCreate()
    {
        request()->merge(['slug' => StringHelper::generateUniqueSlug()]);

        return request()->validate([
            'code' => 'required|unique:shop_main_categories',
            'slug' => 'required|unique:shop_main_categories',
            'name' => 'required|unique:shop_main_categories',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]);
    }

    private static function validateUpdate($slug)
    {
        return request()->validate([
            'code' => [
                'required',
                Rule::unique('shop_main_categories')->ignore($slug, 'slug'),
            ],
            'name' => [
                'required',
                Rule::unique('shop_main_categories')->ignore($slug, 'slug'),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]);
    }
}
