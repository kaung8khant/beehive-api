<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        $brands = Brand::search($request->filter)->paginate(10);
        $this->optimizeBrands($brands);
        return CollectionHelper::removePaginateLinks($brands);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $brand = Brand::create($request->validate([
            'name' => 'required|unique:brands',
            'slug' => 'required|unique:brands',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]));

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'brands', $brand->slug);
        }

        return response()->json($brand, 201);
    }

    public function show(Brand $brand)
    {
        return response()->json($brand->load('products'), 200);
    }

    public function update(Request $request, Brand $brand)
    {
        $brand->update($request->validate([
            'name' => [
                'required',
                Rule::unique('brands')->ignore($brand->id),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]));

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'brands', $brand->slug);
        }

        return response()->json($brand, 200);
    }

    public function destroy(Brand $brand)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        foreach ($brand->images as $image) {
            $this->deleteFile($image->slug);
        }

        $brand->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    private function optimizeBrands($brands)
    {
        $brands->makeHidden(['id', 'created_by', 'updated_by']);
    }
}
