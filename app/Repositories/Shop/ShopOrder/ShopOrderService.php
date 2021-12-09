<?php

namespace App\Repositories\Shop\ShopOrder;

use App\Events\ShopOrderUpdated;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Helpers\ShopOrderHelper;
use App\Helpers\v3\OrderHelper;
use App\Services\MessageService\MessagingService;
use Illuminate\Support\Facades\DB;

class ShopOrderService
{
    private $shopOrderRepository;
    private $messageService;

    public function __construct(ShopOrderRepositoryInterface $shopOrderRepository, MessagingService $messageService)
    {
        $this->shopOrderRepository = $shopOrderRepository;
        $this->messageService = $messageService;
    }

    public function store($validatedData)
    {
        $validatedData = $this->prepareProductVariants($validatedData);
        $customer = $this->shopOrderRepository->getCustomerBySlug($validatedData['customer_slug']);

        if (isset($validatedData['promo_code'])) {
            $validatedData = OrderHelper::getPromoData($validatedData, $customer);
        }

        if ($validatedData['payment_mode'] === 'Credit') {
            $validatedData = OrderHelper::checkCredit($validatedData, $customer->id);
        }

        $paymentData = [];
        if (!in_array($validatedData['payment_mode'], ['COD', 'Credit'])) {
            $paymentData = $this->paymentService->createTransaction($validatedData, 'shop');
        }

        $order = $this->shopOrderTransaction($validatedData, $customer);

        if ($validatedData['payment_mode'] === 'KPay') {
            $order['prepay_id'] = $paymentData['Response']['prepay_id'];
        } elseif ($validatedData['payment_mode'] === 'CBPay') {
            $order->update(['payment_reference' => $paymentData['transRef']]);
            $order['mer_dqr_code'] = $paymentData['merDqrCode'];
        }

        return $order;
    }

    private function prepareProductVariants($validatedData)
    {
        $orderItems = [];
        $subTotal = 0;
        $commission = 0;
        $tax = 0;

        foreach ($validatedData['order_items'] as $key => $value) {
            $productId = $this->shopOrderRepository->getProductIdBySlug($value['slug']);
            $productVariant = $this->shopOrderRepository->getProductVariantBySlug($value['variant_slug']);

            $this->checkProductAndVariant($productId, $productVariant, $key);

            $item['slug'] = $value['slug'];
            $item['name'] = $productVariant->product->name;
            $item['quantity'] = $value['quantity'];
            $item['price'] = $productVariant->price;
            $item['amount'] = $productVariant->price;
            $item['vendor_price'] = $productVariant->vendor_price;
            $item['tax'] = ($item['price'] - $productVariant->discount) * $productVariant->tax * 0.01;
            $item['discount'] = $productVariant->discount;
            $item['variant'] = $productVariant->variant;
            $item['product_id'] = $productId;
            $item['commission'] = max(($item['price'] - $item['vendor_price']) * $value['quantity'], 0);

            $subTotal += ($item['price'] - $productVariant->discount) * $value['quantity'];

            $commission += $item['commission'];
            $tax += ($item['price'] - $productVariant->discount) * $productVariant->tax * 0.01 * $value['quantity'];

            array_push($orderItems, $item);
        }

        $validatedData['order_items'] = $orderItems;
        $validatedData['subTotal'] = $subTotal;
        $validatedData['commission'] = $commission;
        $validatedData['tax'] = $tax;

        if (!isset($validatedData['delivery_fee'])) {
            $validatedData['delivery_fee'] = 0;
        }

        return $validatedData;
    }

    private function checkProductAndVariant($productId, $productVariant, $key)
    {
        if (!$productVariant) {
            throw new ForbiddenException('The order_items.' . $key . '.variant is disabled.');
        }

        if ($productId !== $productVariant->product->id) {
            throw new BadRequestException('The order_items.' . $key . '.variant_slug must be part of the product_slug.', 400);
        }
    }

    private function shopOrderTransaction($validatedData, $customer)
    {
        $order = DB::transaction(function () use ($validatedData) {
            $order = $this->shopOrderRepository->create($validatedData);
            ShopOrderHelper::createOrderContact($order->id, $validatedData['customer_info'], $validatedData['address']);
            ShopOrderHelper::createShopOrderItem($order->id, $validatedData['order_items']);
            ShopOrderHelper::createOrderStatus($order);
            return $order->refresh()->load(['contact']);
        });

        event(new ShopOrderUpdated($order));

        ShopOrderHelper::notifySystem($order, $validatedData['order_items'], $customer->phone_number, $this->messageService);

        return $order;
    }
}
