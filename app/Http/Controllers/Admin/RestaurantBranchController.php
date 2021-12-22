<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Restaurant\RestaurantBranch\RestaurantBranchCreateRequest;
use App\Repositories\Restaurant\RestaurantBranch\RestaurantBranchRepositoryInterface;
use App\Repositories\Restaurant\RestaurantBranch\RestaurantBranchTagsCategoriesUpdateRequest;
use App\Repositories\Restaurant\RestaurantBranch\RestaurantBranchUpdateRequest;

class RestaurantBranchController extends Controller
{
    private $branchRepository;

    public function __construct(RestaurantBranchRepositoryInterface $branchRepository)
    {
        $this->branchRepository = $branchRepository;
    }

    public function index()
    {
        $branches = $this->branchRepository->all();
        $this->optimizeBranches($branches);
        return CollectionHelper::removePaginateLinks($branches);
    }

    public function show($slug)
    {
        return $this->branchRepository->find($slug)->load(['restaurant']);
    }

    public function store(RestaurantBranchCreateRequest $request)
    {
        $branch = $this->branchRepository->create($request->validated())->refresh()->load('restaurant');
        return response()->json($branch, 201);
    }

    public function update(RestaurantBranchUpdateRequest $request, $slug)
    {
        return $this->branchRepository->update($slug, $request->validated())->load('restaurant');
    }

    public function destroy($slug)
    {
        return $this->branchRepository->delete($slug);
    }

    public function toggleEnable($slug)
    {
        return $this->branchRepository->toggleEnable($slug);
    }

    public function multipleStatusUpdate()
    {
        $validatedData = request()->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\Shop,slug',
            'is_enable' => 'required|boolean',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $this->branchRepository->update($slug, ['is_enable' => request('is_enable')]);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function toggleFreeDelivery($slug)
    {
        return $this->branchRepository->toggleFreeDelivery($slug);
    }

    public function updateSearchIndex($slug)
    {
        $branch = $this->branchRepository->update($slug, request()->validate([
            'search_index' => 'required|numeric',
        ]));

        return response()->json($branch->load(['restaurant']), 200);
    }

    public function getBranchesByRestaurant($slug)
    {
        $branches = $this->branchRepository->getAllByRestaurant($slug);
        $this->optimizeBranches($branches);
        return CollectionHelper::removePaginateLinks($branches);
    }

    public function addAvailableMenus($slug)
    {
        $branch = $this->branchRepository->find($slug);

        request()->validate([
            'available_menus.*' => 'exists:App\Models\Menu,slug',
        ]);

        $branch->availableMenus()->detach();
        $branch->availableMenus()->attach($this->branchRepository->getMenuIdsBySlugs(request('available_menus')));

        return $branch->load(['availableMenus', 'restaurant']);
    }

    public function removeAvailableMenus($slug)
    {
        $branch = $this->branchRepository->find($slug);

        request()->validate([
            'available_menus.*' => 'exists:App\Models\Menu,slug',
        ]);

        $branch->availableMenus()->detach($this->branchRepository->getMenuIdsBySlugs(request('available_menus')));

        return $branch->load(['availableMenus', 'restaurant']);
    }

    public function toggleAvailable($slug, $menuSlug)
    {
        $this->branchRepository->toggleAvailable($slug, $menuSlug);
        return response()->json(['message' => 'Success.'], 200);
    }

    public function updateWithTagsAndCategories(RestaurantBranchTagsCategoriesUpdateRequest $request, $slug)
    {
        $branch = $this->branchRepository->update($slug, $request->validated());
        $restaurant = $this->branchRepository->findRestaurant($branch->restaurant_id);

        $restaurantTags = $this->branchRepository->getRestaurantTagIdsBySlugs(request('restaurant_tags'));
        $restaurant->availableTags()->detach();
        $restaurant->availableTags()->attach($restaurantTags);

        if (request('available_categories')) {
            $restaurantCategories = $this->branchRepository->getRestaurantCategoryIdsBySlugs(request('available_categories'));
            $restaurant->availableCategories()->detach();
            $restaurant->availableCategories()->attach($restaurantCategories);
        }

        return response()->json($branch->load(['restaurant']), 200);
    }

    private function optimizeBranches($branches)
    {
        $branches->load(['restaurant']);

        foreach ($branches as $branch) {
            $branch->makeHidden(['restaurant_id', 'address', 'city', 'township', 'notify_numbers', 'latitude', 'longitude', 'created_by', 'updated_by']);
            $branch->restaurant->makeHidden(['is_enable', 'commission', 'rating', 'images', 'covers', 'first_order_date', 'created_by', 'updated_by']);
        }
    }

    /**
     * Do not delete this method. This route is only for debugging purpose.
     *
     * @author Aung Thu Moe
     */
    public function getAll()
    {
        return $this->branchRepository->getBranchesMap();
    }
}
