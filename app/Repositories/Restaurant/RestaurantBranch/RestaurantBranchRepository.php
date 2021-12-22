<?php

namespace App\Repositories\Restaurant\RestaurantBranch;

use App\Events\DataChanged;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use App\Models\RestaurantTag;
use App\Repositories\BaseRepository;

class RestaurantBranchRepository extends BaseRepository implements RestaurantBranchRepositoryInterface
{
    public function __construct(RestaurantBranch $model)
    {
        parent::__construct($model);
    }

    public function all()
    {
        if (request('filter')) {
            return $this->model->search(request('filter'))->paginate(10);
        } else {
            return $this->model->orderBy('search_index', 'desc')->orderBy('id', 'desc')->paginate(10);
        }
    }

    public function getAllByRestaurant($slug)
    {
        $restaurantId = Restaurant::where('slug', $slug)->firstOrFail()->id;

        if (request('filter')) {
            return RestaurantBranch::search(request('filter'))->where('restaurant_id', $restaurantId)->paginate(10);
        } else {
            return RestaurantBranch::where('restaurant_id', $restaurantId)->orderBy('search_index', 'desc')->orderBy('id', 'desc')->paginate(10);
        }
    }

    public function create(array $attributes)
    {
        $branch = $this->model->create($attributes);

        if (auth('users')->check()) {
            DataChanged::dispatch($this->user, 'create', $this->model->getTable(), $branch->slug, request()->url(), 'success', $attributes);
        }

        $menuIds = Menu::where('restaurant_id', $branch->restaurant_id)->pluck('id');
        $branch->availableMenus()->attach($menuIds);

        return $branch;
    }

    public function toggleFreeDelivery($slug)
    {
        $branch = $this->model->where('slug', $slug)->firstOrFail();
        $attributes = ['free_delivery' => !$branch->free_delivery];
        $branch->update($attributes);
        DataChanged::dispatch($this->user, 'update', $this->model->getTable(), $branch->slug, request()->url(), 'success', $attributes);

        return response()->json(['message' => 'Success.'], 200);
    }

    public function getMenuIdsBySlugs($slugs)
    {
        return Menu::whereIn('slug', $slugs)->pluck('id');
    }

    public function toggleAvailable($slug, $menuSlug)
    {
        $branch = $this->model->where('slug', $slug)->firstOrFail();
        $menu = Menu::where('slug', $menuSlug)->firstOrFail();

        $branch->availableMenus()->sync([
            $menu->id => request()->validate(['is_available' => 'required|boolean']),
        ], false);

        DataChanged::dispatch($this->user, 'update', 'restaurant_branch_menu_map', $menu->slug, request()->url(), 'success', request()->all());
    }

    public function findRestaurant($id)
    {
        return Restaurant::find($id);
    }

    public function getRestaurantTagIdsBySlugs($slugs)
    {
        return RestaurantTag::whereIn('slug', $slugs)->pluck('id');
    }

    public function getRestaurantCategoryIdsBySlugs($slugs)
    {
        return RestaurantCategory::whereIn('slug', $slugs)->pluck('id');
    }

    /**
     * Do not delete this method. This route is only for debugging purpose.
     *
     * @author Aung Thu Moe
     */
    public function getBranchesMap()
    {
        return RestaurantBranch::exclude([
            'search_index', 'city', 'township', 'contact_number', 'notify_numbers', 'opening_time', 'closing_time', 'is_enable', 'free_delivery', 'pre_order', 'created_by', 'updated_by',
        ])
            ->with([
                'restaurant' => fn($query) => $query->exclude(['is_enable', 'created_by', 'updated_by', 'commission']),
            ])
            ->get()
            ->map(function ($branch) {
                $branch->restaurant->setAppends([]);
                return $branch;
            });
    }
}
