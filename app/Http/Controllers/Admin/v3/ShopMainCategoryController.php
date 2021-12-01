<?php

namespace App\Http\Controllers\Admin\v3;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\ShopMainCategory\ShopMainCategoryCreateRequest;
use App\Repositories\Shop\ShopMainCategory\ShopMainCategoryRepositoryInterface;
use App\Repositories\Shop\ShopMainCategory\ShopMainCategoryUpdateRequest;
use Illuminate\Database\QueryException;

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

    public function show($slug)
    {
        return $this->mainCategoryRepository->find($slug);
    }

    public function store(ShopMainCategoryCreateRequest $request)
    {
        try {
            $mainCategory = $this->mainCategoryRepository->create($request->validated());
            return response()->json($mainCategory, 201);
        } catch (QueryException $e) {
            return ResponseHelper::generateValidateError('code', 'The code has already been taken.');
        }
    }

    public function update(ShopMainCategoryUpdateRequest $request, $slug)
    {
        try {
            return $this->mainCategoryRepository->update($slug, $request->validated());
        } catch (QueryException $e) {
            return ResponseHelper::generateValidateError('code', 'The code has already been taken.');
        }
    }

    public function destroy($slug)
    {
        if ($this->mainCategoryRepository->checkProducts($slug)) {
            return response()->json(['message' => 'Cannot delete product type if there is a linked product.'], 403);
        }

        return $this->mainCategoryRepository->delete($slug);
    }

    public function updateSearchIndex($slug)
    {
        return $this->mainCategoryRepository->update($slug, request()->validate([
            'search_index' => 'required|numeric',
        ]));
    }
}
