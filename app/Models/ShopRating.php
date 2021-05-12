<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopRating extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'shop_order_id',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['customer'];

    public function getCustomerAttribute()
    {
        $customer = Customer::where('id', $this->source_id)->firstOrFail();

        return [
            'slug' => $customer->slug,
            'name' => $customer->name,
            'phone_number' => $customer->phone_number,
        ];
    }

    public function shopOrder()
    {
        return $this->belongsTo(ShopOrder::class);
    }
}
