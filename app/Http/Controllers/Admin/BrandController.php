<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\Brand\BrandRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

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

    public function store()
    {
        try {
            $brand = $this->brandRepository->create(self::validateCreate());
            return response()->json($brand, 201);
        } catch (QueryException $e) {
            return ResponseHelper::generateValidateError('code', 'The code has already been taken.');
        }
    }

    public function update($slug)
    {
        try {
            return $this->brandRepository->update($slug, self::validateUpdate($slug));
        } catch (QueryException $e) {
            return ResponseHelper::generateValidateError('code', 'The code has already been taken.');
        }
    }

    public function destroy($slug)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        $this->brandRepository->delete($slug);
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    private static function validateCreate()
    {
        request()->merge(['slug' => StringHelper::generateUniqueSlug()]);

        return request()->validate([
            'code' => 'required|unique:brands|size:4',
            'slug' => 'required|unique:brands',
            'name' => 'required|unique:brands',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]);
    }

    private static function validateUpdate($slug)
    {
        return request()->validate([
            'code' => [
                'required',
                // Rule::unique('brands')->ignore($slug, 'slug'),
                'size:4',
            ],
            'name' => [
                'required',
                Rule::unique('brands')->ignore($slug, 'slug'),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]);
    }
}
