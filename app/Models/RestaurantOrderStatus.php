<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RestaurantOrderStatus extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'restaurant_order_id',
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

    public function restaurantOrder()
    {
        return $this->belongsTo(RestaurantOrder::class);
    }
}
