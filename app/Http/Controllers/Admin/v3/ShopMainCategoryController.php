<?php

namespace App\Http\Controllers\Admin\v3;

use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\ShopMainCategory\ShopMainCategoryRepositoryInterface;
use Illuminate\Database\QueryException;
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
        try {
            $mainCategory = $this->mainCategoryRepository->create(self::validateCreate());
            return response()->json($mainCategory, 201);
        } catch (QueryException $e) {
            return ResponseHelper::generateValidateError('code', 'The code has already been taken.');
        }
    }

    public function show($slug)
    {
        return $this->mainCategoryRepository->find($slug);
    }

    public function update($slug)
    {
        try {
            return $this->mainCategoryRepository->update($slug, self::validateUpdate($slug));
        } catch (QueryException $e) {
            return ResponseHelper::generateValidateError('code', 'The code has already been taken.');
        }
    }

    public function destroy($slug)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        return $this->mainCategoryRepository->delete($slug);
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
            'code' => 'required|unique:shop_main_categories|size:2',
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
                'size:2',
            ],
            'name' => [
                'required',
                Rule::unique('shop_main_categories')->ignore($slug, 'slug'),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]);
    }
}
