<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\Brand\BrandCreateRequest;
use App\Repositories\Shop\Brand\BrandRepositoryInterface;
use App\Repositories\Shop\Brand\BrandUpdateRequest;
use Illuminate\Database\QueryException;

class BrandController extends Controller
{
    private $brandRepository;

    public function __construct(BrandRepositoryInterface $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    public function index()
    {
        $brands = $this->brandRepository->all();
        $brands->makeHidden(['created_by', 'updated_by']);
        return CollectionHelper::removePaginateLinks($brands);
    }

    public function show($slug)
    {
        return $this->brandRepository->find($slug);
    }

    public function store(BrandCreateRequest $request)
    {
        try {
            $brand = $this->brandRepository->create($request->validated());
            return response()->json($brand, 201);
        } catch (QueryException $e) {
            return ResponseHelper::generateValidateError('code', 'The code has already been taken.');
        }
    }

    public function update(BrandUpdateRequest $request, $slug)
    {
        try {
            return $this->brandRepository->update($slug, $request->validated());
        } catch (QueryException $e) {
            return ResponseHelper::generateValidateError('code', 'The code has already been taken.');
        }
    }

    public function destroy($slug)
    {
        if ($this->brandRepository->checkProducts($slug)) {
            return response()->json(['message' => 'Cannot delete brand if there is a linked product.'], 403);
        }

        return $this->brandRepository->delete($slug);
    }
}
