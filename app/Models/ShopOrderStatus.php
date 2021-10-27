<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShopOrderStatus extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'shop_order_vendor_id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::guard('users')->check()) {
                $model->created_by = Auth::guard('users')->user()->id;
            }
        });
    }

    public function getCreatedByAttribute($value)
    {
        return DB::table('users')->where('id', $value)->select('slug', 'username', 'name', 'phone_number')->first();
    }

    public function vendor()
    {
        return $this->belongsTo(ShopOrderVendor::class, 'shop_order_vendor_id');
    }
}
