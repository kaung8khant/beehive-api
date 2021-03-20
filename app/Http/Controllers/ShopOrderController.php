<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\ShopOrder;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class ShopOrderController extends Controller
{
    use StringHelper;

    use ResponseHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Get(
     *      path="/api/v2/admin/shop-orders",
     *      operationId="getShopOrderLists",
     *      tags={"Shop Orders"},
     *      summary="Get list of shop orders",
     *      description="Returns list of shop orders",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="filter",
     *          description="Filter",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function index()
    {
        // $customer_id = Auth::guard('customers')->user()->id;
        $shopOrders = ShopOrder::with('contact')
            ->with('items')
            // ->where('customer_id', $customer_id)
            ->latest()
            ->paginate(10)
            ->items();

        return $this->generateResponse($shopOrders, 200);
    }

    public function getShopOrders(Request $request, $slug)
    {
        $shopOrders = ShopOrder::with('contact')->with('items')
            // ->whereDate('order_date', '>=', $request->from)
            // ->whereDate('order_date', '<=', $request->to)
            ->where('slug', $slug)
            ->whereHas('contact', function ($q) use ($request) {
                $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', $request->filter);
            })->orWhere('slug', $request->filter)
            ->latest()
            ->paginate(10)
            ->items();

        return $this->generateResponse($shopOrders, 200);
    }
}
