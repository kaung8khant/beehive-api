<?php

namespace App\Repositories\Shop\ShopOrder;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Helpers\PromocodeHelper;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promocode;

class ShopOrderService
{
    public function store($validatedData)
    {
        $validatedData = $this->prepareProductVariants($validatedData);

        $customer = Customer::where('slug', $validatedData['customer_slug'])->first();
        if (isset($validatedData['promo_code'])) {
            $validatedData = $this->getPromoData($validatedData, $customer);
        }

        return $validatedData;
    }

    private function prepareProductVariants($validatedData)
    {
        $orderItems = [];
        $subTotal = 0;
        $commission = 0;
        $tax = 0;

        foreach ($validatedData['order_items'] as $key => $value) {
            $productId = Product::where('slug', $value['slug'])->value('id');
            $productVariant = $this->getProductVariant($value['variant_slug']);

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

    private function getProductVariant($slug)
    {
        return ProductVariant::with('product')->where('slug', $slug)->where('is_enable', 1)->first();
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

    private function getPromoData($validatedData, $customer)
    {
        $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest()->first();
        if (!$promocode) {
            throw new ForbiddenException('Promocode not found.');
        }

        $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'shop');
        if (!$validUsage) {
            throw new ForbiddenException('Invalid promocode usage for shop.');
        }

        $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer, 'shop');
        if (!$validRule) {
            throw new ForbiddenException('Invalid promocode.');
        }

        $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'shop');

        $validatedData['promocode_id'] = $promocode->id;
        $validatedData['promocode'] = $promocode->code;
        $validatedData['promocode_amount'] = min($validatedData['subTotal'] + $validatedData['tax'], $promocodeAmount);

        return $validatedData;
    }
}
