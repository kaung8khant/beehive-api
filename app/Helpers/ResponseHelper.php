<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

trait ResponseHelper
{
    protected function generateResponse($data, $status, $message = false)
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
        if ($type === 'array' || $type === 'home') {
            foreach ($data as $branch) {
                $branch->restaurant->is_favorite = $this->checkFavoriteRestaurant($branch->restaurant->id);
                unset($branch->restaurant->customers);
            }

            if ($type === 'home') {
                return $data;
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

    public function generateProductResponse($data, $status, $type = 'array')
    {
        if (empty($data)) {
            if ($type === "home") {
                return $data;
            }
            return $this->generateResponse($data, $status);
        }

        if ($type === 'array' || $type === 'home') {

            foreach ($data as $product) {
                $product['is_favorite'] = $this->checkFavoriteProduct($product->id);
                unset($product->customers);
            }
            if ($type === "home") {
                return $data;
            }

        } elseif ($type === 'arrobj') {
            foreach ($data as $arrobj) {
                foreach ($arrobj as $product) {
                    $product['is_favorite'] = $this->checkFavoriteProduct($product->id);
                    unset($product->customers);
                }
            }
        } elseif ($type === 'cattag') {
            foreach ($data as $cat) {
                foreach ($cat->products as $product) {
                    $product->is_favorite = $this->checkFavoriteProduct($product->id);
                    unset($product->customers);
                }
            }
        } else {
            $data['is_favorite'] = $this->checkFavoriteProduct($data->id);
            unset($data['customers']);
        }

        return $this->generateResponse($data, $status);
    }
    public function generateShopOrderResponse($data, $status, $type = 'obj')
    {
        $items = collect([]);
        if ($type === "obj") {
            foreach ($data->vendors as $vendor) {
                $list = $vendor->items;
                foreach ($vendor->items as $item) {
                    $item['images'] = $item->product->images;
                    $item['order_status'] = $vendor->order_status;
                }
                $items = $items->concat($list);
            }

            $data['items'] = $items;
            $data->makeHidden("vendors");

        } else if ($type === "array") {
            foreach ($data as $shopOrder) {
                foreach ($shopOrder->vendors as $vendor) {
                    $list = $vendor->items;
                    foreach ($vendor->items as $item) {
                        $item['images'] = $item->product->images;
                        $item['order_status'] = $vendor->order_status;
                    }
                    $items = $items->concat($list);
                }
                $shopOrder['items'] = $items;
                $shopOrder->makeHidden("vendors");
            }
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

    private function checkFavoriteProduct($product_id)
    {
        if ($customer = Auth::guard('customers')->user()) {
            return $customer->favoriteProducts->pluck('id')->contains($product_id);
        }

        return false;
    }
}
