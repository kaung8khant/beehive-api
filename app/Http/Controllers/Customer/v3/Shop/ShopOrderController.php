<?php

namespace App\Http\Controllers\Customer\v3\Shop;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ServerException;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\ShopOrder\ShopOrderCreateRequest;
use App\Repositories\Shop\ShopOrder\ShopOrderRepositoryInterface;
use App\Repositories\Shop\ShopOrder\ShopOrderService;

class ShopOrderController extends Controller
{
    private $shopOrderRepository;

    public function __construct(ShopOrderRepositoryInterface $shopOrderRepository)
    {
        $this->shopOrderRepository = $shopOrderRepository;
    }

    public function index()
    {
        $shopOrder = $this->shopOrderRepository->getAllByCustomer();
        return ResponseHelper::generateShopOrderResponse($shopOrder, 200, 'array');
    }

    public function show($slug)
    {
        $shopOrder = $this->shopOrderRepository->find($slug);
        return ResponseHelper::generateShopOrderResponse($shopOrder, 200);
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
            logger()->critical('Customer shop order v3 error: ' . auth('customers')->user()->phone_number);
            throw $e;
        }
    }

    public function destroy($slug)
    {
        return ResponseHelper::generateResponse('You cannot cancel order at the moment. Please contact support.', 403, true);

        //     $shopOrder = ShopOrder::with('vendors')
        //         ->where('customer_id', $this->customer->id)
        //         ->where('slug', $slug)
        //         ->firstOrFail();

        //     if ($shopOrder->order_status === 'delivered' || $shopOrder->order_status === 'cancelled') {
        //         return $this->generateResponse('The order has already been ' . $shopOrder->order_status . '.', 406, true);
        //     }

        //     $message = 'Your order has been cancelled.';
        //     $smsData = SmsHelper::prepareSmsData($message);
        //     $uniqueKey = StringHelper::generateUniqueSlug();

        //     SendSms::dispatch($uniqueKey, [$this->customer->phone_number], $message, 'order', $smsData, $this->messageService);
        //     OrderHelper::createOrderStatus($shopOrder->id, 'cancelled');

        //     return $this->generateResponse($message, 200, true);
    }
}
