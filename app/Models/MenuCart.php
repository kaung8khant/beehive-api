<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuCart extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'customer_id',
        'restaurant_branch_id',
        'created_at',
        'updated_at',
    ];

    public function restaurantBranch()
    {
        return $this->belongsTo(RestaurantBranch::class);
    }

    public function menuCartItems()
    {
        return $this->hasMany(MenuCartItem::class);
    }
}
