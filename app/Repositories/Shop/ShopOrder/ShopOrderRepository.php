<?php

namespace App\Repositories\Shop\ShopOrder;

use App\Models\ShopOrder;
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
}
