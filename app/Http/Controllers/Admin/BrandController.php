<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\Brand\BrandRepositoryInterface;
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
        $brand = $this->brandRepository->create(self::validateCreate());
        return response()->json($brand, 201);
    }

    public function update($slug)
    {
        return $this->brandRepository->update($slug, self::validateUpdate($slug));
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
            'name' => 'required|unique:brands',
            'slug' => 'required|unique:brands',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]);
    }

    private static function validateUpdate($slug)
    {
        return request()->validate([
            'name' => [
                'required',
                Rule::unique('brands')->ignore($slug, 'slug'),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]);
    }
}
