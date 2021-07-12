<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantOrderDriver extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
    ];

    public function restaurantsOrder()
    {
        return $this->belongsTo(RestaurantOrder::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function status()
    {
        return $this->hasMany(RestaurantOrderDriverStatus::class);
    }
}
