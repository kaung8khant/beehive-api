<?php

namespace App\Http\Controllers\Customer\v3;

use App\Helpers\CacheHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\RestaurantBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RestaurantBranchController extends Controller
{
    use ResponseHelper;

    public function getAllBranches(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $restaurantBranches = $this->getBranches($request)
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhereHas('restaurant', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->filter . '%');
                    })
                    ->orWhereHas('availableMenus', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->filter . '%')
                            ->orWhere('description', 'LIKE', '%' . $request->filter . '%')
                            ->orWhereHas('restaurantCategory', function ($p) use ($request) {
                                $p->where('name', 'LIKE', '%' . $request->filter . '%');
                            });
                    });
            })
            ->orderBy('search_index', 'desc')
            ->orderBy('distance', 'asc')
            ->paginate($request->size);

        $this->optimizeBranches($restaurantBranches);
        return $this->generateBranchResponse($restaurantBranches, 200, 'array', $restaurantBranches->lastPage());
    }

    private function validateLocation($request)
    {
        return Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);
    }

    private function getBranches($request)
    {
        $query = RestaurantBranch::with('restaurant');
        return $this->getBranchQuery($query, $request);
    }

    private function getBranchQuery($query, $request)
    {
        $radius = CacheHelper::getRestaurantSearchRadius();

        return $query->with('restaurant')
            ->with('restaurant.availableTags')
            ->selectRaw('id, search_index, slug, name, address, contact_number, opening_time, closing_time, is_enable, free_delivery, pre_order, restaurant_id,
            @distance := ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->lat, $request->lng, $request->lat])
            ->selectRaw("IF(@distance < ?, true, false) AS instant_order", [$radius])
            ->whereHas('restaurant', function ($q) {
                $q->where('is_enable', 1);
            })
            ->where('is_enable', 1);
    }

    private function optimizeBranches($branches)
    {
        foreach ($branches as $branch) {
            $branch->makeHidden(['address', 'contact_number']);
            $branch->restaurant->makeHidden(['created_by', 'updated_by', 'commission']);
            $branch->restaurant->setAppends(['rating', 'images']);
        }
    }
}
