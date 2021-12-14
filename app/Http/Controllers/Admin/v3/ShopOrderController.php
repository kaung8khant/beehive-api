<?php

namespace App\Http\Controllers\Admin\v3;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ServerException;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\ShopOrder\ShopOrderCreateRequest;
use App\Repositories\Shop\ShopOrder\ShopOrderRepositoryInterface;
use App\Repositories\Shop\ShopOrder\ShopOrderService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class ShopOrderController extends Controller
{
    private $shopOrderRepository;
    private $resMes;

    public function __construct(ShopOrderRepositoryInterface $shopOrderRepository)
    {
        $this->shopOrderRepository = $shopOrderRepository;
        $this->resMes = config('response-en.shop_order');
    }

    public function index()
    {
        $shopOrders = $this->shopOrderRepository->all()->makeHidden(['vendors']);
        return ResponseHelper::generateResponse($shopOrders, 200);
    }

    public function show($slug)
    {
        $shopOrder = $this->shopOrderRepository->find($slug)->load(['contact', 'vendors', 'vendors.shop', 'vendors.shopOrderStatuses', 'drivers', 'drivers.status', 'drivers.driver']);

        if (cache('shopOrder:' . $shopOrder->slug)) {
            $shopOrder['assign'] = 'pending';
        } else {
            $shopOrder['assign'] = null;
        }

        return ResponseHelper::generateResponse($shopOrder, 200);
    }

    public function store(ShopOrderCreateRequest $request, ShopOrderService $shopOrderService)
    {
        try {
            $order = $shopOrderService->store($request->validated());
            return ResponseHelper::generateShopOrderResponse($order, 201);
        } catch (ForbiddenException $e) {
            return ResponseHelper::generateResponse($e->getMessage(), 403, true);
        } catch (BadRequestException $e) {
            return ResponseHelper::generateResponse($e->getMessage(), 400, true);
        } catch (ServerException $e) {
            return ResponseHelper::generateResponse($e->getMessage(), 500, true);
        } catch (\Exception $e) {
            $url = explode('/', $request->path());

            if ($url[2] === 'admin' || $url[2] === 'vendor') {
                $auth = $url[2] === 'admin' ? 'Admin' : 'Vendor';
                $phoneNumber = $this->shopOrderRepository->getCustomerBySlug(request('customer_slug'))->phone_number;
                Log::critical($auth . ' shop order ' . $url[1] . ' error: ' . $phoneNumber);
            }

            throw $e;
        }
    }

    public function changeStatus(ShopOrderService $shopOrderService, $slug)
    {
        try {
            $shopOrder = $this->shopOrderRepository->find($slug);
            $shopOrderService->changeOrderStatus($shopOrder);
            return ResponseHelper::generateResponse(sprintf($this->resMes['order_sts_succ'], request('status')), 200, true);
        } catch (ForbiddenException $e) {
            return ResponseHelper::generateResponse($e->getMessage(), 403, true);
        } catch (QueryException $e) {
            return ResponseHelper::generateResponse($e->getMessage(), 500, true);
        }
    }

    public function getVendorOrders($slug)
    {
        if (auth('vendors')->user()->shop_id !== $this->shopOrderRepository->getShopIdBySlug($slug)) {
            abort(404);
        }

        $vendorOrders = $this->shopOrderRepository->getAllByShop($slug)
            ->map(function ($vendor) {
                $vendor->makeHidden(['items']);
                $vendor->shopOrder->makeHidden(['vendors']);
                return $vendor;
            });

        return ResponseHelper::generateResponse($vendorOrders, 200);
    }

    public function cancelOrderItem($orderSlug, $itemId)
    {
        $shopOrder = $this->shopOrderRepository->find($orderSlug);
        $shopOrderItem = $this->shopOrderRepository->findShopOrderItem($itemId);

        $shopOrderItem->delete();
        $commission = 0;

        foreach ($shopOrder->vendors as $vendor) {
            foreach ($vendor->items as $item) {
                $commission += $item->commission;
            }
        }

        $shopOrder->update(['commission' => $commission]);
        return response()->json(['message' => 'Successfully cancelled.'], 200);
    }

    public function updatePayment($slug)
    {
        $this->shopOrderRepository->update($slug, request()->validate([
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'payment_status' => 'required|in:success,failed,pending',
        ]));

        return ResponseHelper::generateResponse('The order payment has successfully been updated.', 200, true);
    }
}
