<?php

namespace App\Repositories\Shop\ShopOrder;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopOrderContact;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderStatus;
use App\Models\ShopOrderVendor;
use App\Repositories\BaseRepository;

class ShopOrderRepository extends BaseRepository implements ShopOrderRepositoryInterface
{
    public function __construct(ShopOrder $model)
    {
        parent::__construct($model);
    }

    public function all()
    {
        return $this->model->exclude(['special_instruction', 'delivery_mode', 'promocode_amount', 'customer_id', 'created_by', 'updated_by'])
            ->with(['contact' => function ($query) {
                $query->exclude(['house_number', 'floor', 'street_name', 'latitude', 'longitude']);
            }])
            ->whereBetween('order_date', array(request('from'), request('to')))
            ->where(function ($query) {
                $query->where('id', ltrim(ltrim(request('filter'), 'BHS'), '0'))
                    ->orWhereHas('contact', function ($q) {
                        $q->where('phone_number', request('filter'))
                            ->orWhere('customer_name', 'LIKE', '%' . request('filter') . '%');
                    });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getAllByShop($slug)
    {
        $shopId = $this->getShopIdBySlug($slug);

        return ShopOrderVendor::with(['shopOrder', 'shopOrder.contact'])
            ->where('shop_id', $shopId)
            ->where(function ($query) {
                $query->whereHas('shopOrder', function ($q) {
                    $q
                        ->where('id', ltrim(ltrim(request('filter'), 'BHS'), '0'));
                })
                    ->orWhereHas('shopOrder.contact', function ($q) {
                        $q->where('customer_name', 'LIKE', '%' . request('filter') . '%')
                            ->orWhere('phone_number', request('filter'));
                    });
            })
            ->where(function ($query) {
                $query->whereHas('shopOrder', function ($q) {
                    $q->whereBetween('order_date', array(request('from'), request('to')));
                });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getShopIdBySlug($slug)
    {
        return Shop::where('slug', $slug)->firstOrFail()->id;
    }

    public function getProductIdBySlug($slug)
    {
        return Product::where('slug', $slug)->value('id');
    }

    public function getProductSlugById($id)
    {
        return Product::where('id', $id)->value('slug');
    }

    public function getProductVariantBySlug($slug)
    {
        return ProductVariant::with('product')->where('slug', $slug)->where('is_enable', 1)->first();
    }

    public function getCustomerBySlug($slug)
    {
        return Customer::where('slug', $slug)->firstOrFail();
    }

    public function getShopByProductId($id)
    {
        return Product::where('id', $id)->first()->shop;
    }

    public function getMaxInvoiceNo()
    {
        return $this->model->max('invoice_no');
    }

    public function getVendorsByShopOrderId($shopOrderId)
    {
        return ShopOrderVendor::where('shop_order_id', $shopOrderId)->get();
    }

    public function getVendorByOrderIdAndShopId($shopOrderId, $shopId)
    {
        return ShopOrderVendor::where('shop_order_id', $shopOrderId)->where('shop_id', $shopId)->first();
    }

    public function createShopOrderContact(array $attributes)
    {
        ShopOrderContact::create($attributes);
    }

    public function createShopOrderVendor(array $attributes)
    {
        return ShopOrderVendor::updateOrCreate($attributes);
    }

    public function createShopOrderItem(array $attributes)
    {
        ShopOrderItem::create($attributes);
    }

    public function createShopOrderStatus(array $attributes)
    {
        ShopOrderStatus::create($attributes);
    }

    public function getOrderCancelMessage()
    {
        return Setting::where('key', 'customer_shop_order_cancel')->value('value');
    }
}
