<?php

namespace App\Repositories\Shop\ShopOrder;

use App\Events\ShopOrderUpdated;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Helpers\ShopOrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Helpers\v3\OrderHelper;
use App\Jobs\SendSms;
use App\Services\MessageService\MessagingService;
use App\Services\PaymentService\PaymentService;
use Illuminate\Support\Facades\DB;

class ShopOrderService
{
    private $shopOrderRepository;
    private $messageService;
    private $paymentService;
    private $resMes;

    public function __construct(ShopOrderRepositoryInterface $shopOrderRepository, MessagingService $messageService, PaymentService $paymentService)
    {
        $this->shopOrderRepository = $shopOrderRepository;
        $this->messageService = $messageService;
        $this->paymentService = $paymentService;
        $this->resMes = config('response-en.shop_order');
    }

    public function store($validatedData)
    {
        $customer = $this->shopOrderRepository->getCustomerBySlug($validatedData['customer_slug']);
        $validatedData = $this->prepareProductVariants($validatedData);
        $validatedData['customer_id'] = $customer->id;

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
            $this->createOrderContact($order->id, $validatedData['customer_info'], $validatedData['address']);
            $this->createShopOrderItems($order->id, $validatedData['order_items']);
            $this->createOrderStatus($order);
            return $order->refresh()->load(['contact']);
        });

        event(new ShopOrderUpdated($order));

        ShopOrderHelper::notifySystem($order, $validatedData['order_items'], $customer->phone_number, $this->messageService);

        return $order;
    }

    private function createOrderContact($orderId, $customerInfo, $address)
    {
        $customerInfo = array_merge($customerInfo, $address);
        $customerInfo['shop_order_id'] = $orderId;
        $this->shopOrderRepository->createShopOrderContact($customerInfo);
    }

    private function createShopOrderItems($orderId, $orderItems)
    {
        foreach ($orderItems as $item) {
            $shop = $this->shopOrderRepository->getShopByProductId($item['product_id']);

            $shopOrderVendor = $this->shopOrderRepository->getVendorByOrderIdAndShopId($orderId, $shop->id);

            if (!$shopOrderVendor) {
                $shopOrderVendor = $this->shopOrderRepository->createShopOrderVendor([
                    'slug' => StringHelper::generateUniqueSlugWithTable('shop_order_vendors'),
                    'shop_order_id' => $orderId,
                    'shop_id' => $shop->id,
                ]);
            }

            $item['shop'] = $shop;
            $item['shop_order_vendor_id'] = $shopOrderVendor->id;
            $item['shop_id'] = $shop->id;
            $item['product_name'] = $item['name'];
            $item['amount'] = $item['price'];

            $this->shopOrderRepository->createShopOrderItem($item);
        }
    }

    public function createOrderStatus($order, $orderStatus = 'pending')
    {
        $order->update($this->prepareOrderStatus($order, $orderStatus));

        $shopOrderVendors = $this->shopOrderRepository->getVendorsByShopOrderId($order->id);

        foreach ($shopOrderVendors as $vendor) {
            $vendor->update([
                'order_status' => $orderStatus,
            ]);

            $this->shopOrderRepository->createShopOrderStatus([
                'shop_order_vendor_id' => $vendor->id,
                'status' => $orderStatus,
            ]);
        }
    }

    private function prepareOrderStatus($order, $orderStatus)
    {
        $data['order_status'] = $orderStatus;

        if (in_array($orderStatus, ['pickUp', 'onRoute', 'delivered']) && !$order->invoice_no) {
            $data['invoice_no'] = $this->shopOrderRepository->getMaxInvoiceNo() + 1;
        }

        if ($orderStatus === 'delivered') {
            $paymentStatus = 'success';
        } elseif ($orderStatus === 'cancelled') {
            $paymentStatus = 'failed';
        } else {
            $paymentStatus = null;
        }

        if ($paymentStatus) {
            $data['payment_status'] = $paymentStatus;
        }

        return $data;
    }

    public function changeOrderStatus($shopOrder)
    {
        $this->checkSuperAdminAndPaymentMode($shopOrder);
        $this->createOrderStatus($shopOrder, request('status'));

        $orderItems = $this->getOrderItemsFromOrder($shopOrder);

        $shopOrder['order_status'] = request('status');
        ShopOrderHelper::sendPushNotifications($shopOrder, $orderItems, 'Order Number:' . $shopOrder->invoice_id . ', is now ' . request('status'));

        if (request('status') === 'cancelled') {
            $this->sendSms($shopOrder);
        }

        if (request('status') === 'pickUp') {
            event(new ShopOrderUpdated($shopOrder));
        }
    }

    private function checkSuperAdminAndPaymentMode($shopOrder)
    {
        if ($shopOrder->order_status === 'delivered' || $shopOrder->order_status === 'cancelled') {
            if (!auth('users')->user()->roles->contains('name', 'SuperAdmin')) {
                throw new ForbiddenException(sprintf($this->resMes['order_sts_err'], $shopOrder->order_status));
            }
        }

        if ($shopOrder->payment_mode !== 'COD' && $shopOrder->payment_status !== 'success' && request('status') !== 'cancelled') {
            throw new ForbiddenException($this->resMes['payment_err']);
        }
    }

    private function getOrderItemsFromOrder($shopOrder)
    {
        return $shopOrder->vendors
            ->map(function ($vendor) {
                return $vendor->items;
            })
            ->collapse()
            ->values()
            ->map(function ($item) {
                unset($item->shop);
                $item->slug = $this->shopOrderRepository->getProductSlugById($item->id);
                return $item;
            })->toArray();
    }

    private function sendSms($shopOrder)
    {
        $message = $this->shopOrderRepository->getOrderCancelMessage();
        $message = SmsHelper::parseShopSmsMessage($shopOrder, $message);

        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();
        $phoneNumber = OrderHelper::getCustomerPhoneNumber($shopOrder->customer_id);

        SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData, $this->messageService);
    }
}
