<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

trait ResponseHelper
{
    protected function generateResponse($data, $status, $message = FALSE)
    {
        $response['status'] = $status;

        if ($message) {
            $response['message'] = $data;
        } else {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    protected function generateBranchResponse($data, $status, $type = 'array')
    {
        if ($type === 'array') {
            foreach ($data as $branch) {
                $branch->restaurant->is_favorite = $this->checkFavoriteRestaurant($branch->restaurant->id);
                unset($branch->restaurant->customers);
            }
        } elseif ($type === 'arrobj') {
            foreach ($data as $arrobj) {
                foreach ($arrobj->restaurant_branches as $branch) {
                    $branch->restaurant->is_favorite = $this->checkFavoriteRestaurant($branch->restaurant->id);
                    unset($branch->restaurant->customers);
                }
            }
        } elseif ($type === 'cattag') {
            foreach ($data->restaurant_branches as $branch) {
                $branch->restaurant->is_favorite = $this->checkFavoriteRestaurant($branch->restaurant->id);
                unset($branch->restaurant->customers);
            }
        } else {
            $data->restaurant->is_favorite = $this->checkFavoriteRestaurant($data->restaurant->id);
            unset($data->restaurant->customers);
        }

        return $this->generateResponse($data, $status);
    }

    private function checkFavoriteRestaurant($restaurantId)
    {
        if ($customer = Auth::guard('customers')->user()) {
            return $customer->favoriteRestaurants->pluck('id')->contains($restaurantId);
        }

        return false;
    }
}
