<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\Shop\ShopCreateRequest;
use App\Repositories\Shop\Shop\ShopRepositoryInterface;
use App\Repositories\Shop\Shop\ShopUpdateRequest;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    use FileHelper, StringHelper;

    private $shopRepository;

    public function __construct(ShopRepositoryInterface $shopRepository)
    {
        $this->shopRepository = $shopRepository;
    }

    public function index()
    {
        $shops = $this->shopRepository->all();
        $this->optimizeShops($shops);
        return CollectionHelper::removePaginateLinks($shops);
    }

    public function show($slug)
    {
        return $this->shopRepository->find($slug)->load(['availableTags']);
    }

    public function store(ShopCreateRequest $request)
    {
        $shop = $this->shopRepository->create($request->validated())->refresh()->load(['availableTags']);
        return response()->json($shop, 201);
    }

    public function update(ShopUpdateRequest $request, $slug)
    {
        return $this->shopRepository->update($slug, $request->validated())->load(['availableTags']);
    }

    public function destroy($slug)
    {
        return $this->shopRepository->delete($slug);
    }

    public function toggleEnable($slug)
    {
        return $this->shopRepository->toggleEnable($slug);
    }

    public function multipleStatusUpdate(Request $request)
    {
        $validatedData = request()->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\Shop,slug',
            'is_enable' => 'required|boolean',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $this->shopRepository->update($slug, ['is_enable' => request('is_enable')]);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function toggleOfficial($slug)
    {
        return $this->shopRepository->toggleEnable($slug);
    }

    public function getShopsByBrand($slug)
    {
        $shops = $this->shopRepository->getAllByBrand($slug);
        $this->optimizeShops($shops);
        return CollectionHelper::removePaginateLinks($shops);
    }

    private function optimizeShops($shops)
    {
        $shops->makeHidden(['city', 'township', 'notify_numbers', 'latitude', 'longitude', 'created_by', 'updated_by']);
    }
}
