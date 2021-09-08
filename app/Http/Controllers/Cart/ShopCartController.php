<?php

namespace App\Http\Controllers\Cart;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Admin\v3\ShopOrderController;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCart;
use App\Models\ProductCartItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use stdClass;

class ShopCartController extends CartController
{
    use ResponseHelper;

    private $customer;
    private $resMes;

    public function __construct(Request $request)
    {
        $this->customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $this->resMes = config('response-en');
    }

    public function store(Request $request, Product $product)
    {
        $validator = $this->validateProductCart($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        try {
            $productCart = ProductCart::where('customer_id', $this->customer->id)->first();
            $productData = $this->prepareProductData($product, $request);

            if ($productCart) {
                $productCartItem = $this->getProductCartItem($product->id, $productCart->id, $productData['key']);

                if ($productCartItem) {
                    $productData['quantity'] = $productCartItem->product['quantity'] + $request->quantity;
                    $productCartItem->product = $productData;
                    $productCartItem->save();
                } else {
                    $this->createProductCartItem($productCart->id, $product->id, $productData);
                }

                if ($productCart->promocode) {
                    $request['promo_code'] = $productCart->promocode;
                    $this->applyPromocode($request);
                }
            } else {
                $productCart = DB::transaction(function () use ($product, $productData) {
                    $productCart = $this->createProductCart($this->customer->id);
                    $this->createProductCartItem($productCart->id, $product->id, $productData);
                    return $productCart;
                });
            }

            $data = $this->prepareProductCartData($productCart->refresh()->load('productCartItems'));
            return $this->generateResponse($data, 200);
        } catch (BadRequestException $e) {
            return $this->generateResponse($e->getMessage(), 400, true);
        }
    }

    private function validateProductCart($request)
    {
        return Validator::make($request->all(), [
            'customer_slug' => 'required|exists:App\Models\Customer,slug',
            'quantity' => 'required|integer',
            'variant_slug' => 'required|exists:App\Models\ProductVariant,slug',
        ]);
    }

    private function prepareProductData($product, $request)
    {
        $productVariant = $this->getVariant($product, $request->variant_slug);

        $amount = $productVariant->price;
        $tax = ($amount - $productVariant->discount) * $productVariant->tax * 0.01;
        $discount = $productVariant->discount;

        return [
            'key' => $product->slug . '-' . $productVariant->slug,
            'slug' => $product->slug,
            'name' => $product->name,
            'description' => $product->description,
            'amount' => $amount,
            'tax' => $tax,
            'discount' => $discount,
            'quantity' => $request->quantity,
            'variant' => $productVariant,
            'images' => $product->images,
        ];
    }

    private function getVariant($product, $variantSlug)
    {
        $productVariant = ProductVariant::where('product_id', $product->id)->where('slug', $variantSlug)->first();

        if (!$productVariant) {
            throw new BadRequestException($this->resMes['shop_cart']['variant_err']);
        }

        return $productVariant;
    }

    private function createProductCart($customerId)
    {
        return ProductCart::create([
            'slug' => StringHelper::generateUniqueSlug(),
            'customer_id' => $customerId,
        ]);
    }

    private function createProductCartItem($productCartId, $productId, $productData)
    {
        ProductCartItem::create([
            'product_cart_id' => $productCartId,
            'product_id' => $productId,
            'shop_id' => Product::where('id', $productId)->value('shop_id'),
            'product' => $productData,
        ]);
    }

    private function getProductCartItem($productId, $productCartId, $key)
    {
        return ProductCartItem::where('product_cart_id', $productCartId)
            ->where('product_id', $productId)
            ->get()
            ->first(function ($value) use ($key) {
                return $value->product['key'] === $key;
            });
    }

    public function updateQuantity(Request $request, Product $product)
    {
        $productCart = ProductCart::where('customer_id', $this->customer->id)->first();
        if (!$productCart) {
            return $this->generateResponse($this->resMes['shop_cart']['empty'], 400, true);
        }

        $productCartItem = $this->getProductCartItem($product->id, $productCart->id, $request->key);
        if (!$productCartItem) {
            return $this->generateResponse($this->resMes['shop_cart']['no_item'], 400, true);
        }

        $productData = $productCartItem->product;
        $productData['quantity'] = $request->quantity;

        $productCartItem->product = $productData;
        $productCartItem->save();

        if ($productCart->promocode) {
            $request['promo_code'] = $productCart->promocode;
            $this->applyPromocode($request);
        }

        $data = $this->prepareProductCartData($productCart->refresh()->load('productCartItems'));
        return $this->generateResponse($data, 200);
    }

    public function delete(Request $request, Product $product)
    {
        $productCart = ProductCart::where('customer_id', $this->customer->id)->first();
        if (!$productCart) {
            return $this->generateResponse($this->resMes['shop_cart']['empty'], 400, true);
        }

        $productCartItem = $this->getProductCartItem($product->id, $productCart->id, $request->key);
        if (!$productCartItem) {
            return $this->generateResponse($this->resMes['shop_cart']['no_item'], 400, true);
        }
        $productCartItem->delete();

        if (ProductCartItem::where('product_cart_id', $productCart->id)->count() === 0) {
            $productCart->delete();
            return $this->generateResponse(new stdClass(), 200);
        }

        if ($productCart->promocode) {
            $request['promo_code'] = $productCart->promocode;
            $this->applyPromocode($request);
        }

        $data = $this->prepareProductCartData($productCart->refresh()->load('productCartItems'));
        return $this->generateResponse($data, 200);
    }

    public function deleteCart()
    {
        $productCart = ProductCart::where('customer_id', $this->customer->id)->first();
        if (!$productCart) {
            return $this->generateResponse($this->resMes['shop_cart']['empty'], 400, true);
        }

        $productCart->delete();
        return $this->generateResponse('success', 200, true);
    }

    public function applyPromocode(Request $request)
    {
        $productCart = ProductCart::with('productCartItems')->where('customer_id', $this->customer->id)->first();
        if (!$productCart) {
            return $this->generateResponse($this->resMes['shop_cart']['empty'], 400, true);
        }

        try {
            $request['sub_total'] = $this->getSubTotal($productCart->productCartItems->pluck('product'));
            $request['order_items'] = $this->getOrderItems($productCart->productCartItems);

            $promoData = $this->getPromoData($request, $this->customer, 'shop');

            $productCart->promocode_id = $promoData['promocode_id'];
            $productCart->promocode = $promoData['promocode'];
            $productCart->promo_amount = $promoData['promo_amount'];
            $productCart->save();

            $cartData = $this->prepareProductCartData($productCart);
            return $this->generateResponse($cartData, 200);
        } catch (ForbiddenException $e) {
            return $this->generateResponse($e->getMessage(), 403, true);
        }
    }

    private function getOrderItems($productCartItems)
    {
        return $productCartItems->map(function ($cartItem) {
            return [
                'slug' => $cartItem->product['slug'],
                'quantity' => $cartItem->product['quantity'],
                'variant_slug' => $cartItem->product['variant']['slug'],
            ];
        })->toArray();
    }

    public function removePromocode()
    {
        $productCart = ProductCart::with('productCartItems')->where('customer_id', $this->customer->id)->first();

        if (!$productCart) {
            return $this->generateResponse($this->resMes['shop_cart']['empty'], 400, true);
        }

        $productCart->promocode_id = null;
        $productCart->promocode = null;
        $productCart->promo_amount = 0;
        $productCart->save();

        $cartData = $this->prepareProductCartData($productCart);
        return $this->generateResponse($cartData, 200);
    }

    public function checkout(Request $request)
    {
        $productCart = ProductCart::with('productCartItems')->where('customer_id', $this->customer->id)->first();

        if (!$productCart || !isset($productCart->productCartItems) || $productCart->productCartItems->count() === 0) {
            return $this->generateResponse($this->resMes['shop_cart']['empty'], 400, true);
        }

        try {
            $productIds = $productCart->productCartItems->pluck('product_id')->unique();
            $this->checkShopsAndProducts($productIds);
        } catch (ForbiddenException $e) {
            return $this->generateResponse($e->getMessage(), 400, true);
        }

        $request['promo_code'] = $productCart->promocode;
        $request['order_items'] = $this->getOrderItems($productCart->productCartItems);

        $order = new ShopOrderController($this->getMessageService(), $this->getPaymentService($request->payment_mode));
        $result = $order->store($request);

        if (json_decode($result->getContent(), true)['status'] === 201) {
            $productCart->delete();
        }

        return $result;
    }

    private function checkShopsAndProducts($productIds)
    {
        foreach ($productIds as $productId) {
            $product = Product::with('shop')->where('id', $productId)->first();

            if (!$product->is_enable) {
                throw new ForbiddenException(sprintf($this->resMes['product']['enable'], $product->name));
            }

            if (!$product->shop->is_enable) {
                throw new ForbiddenException(sprintf($this->resMes['shop']['enable'], $product->shop->name));
            }
        }
    }
}
