<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShopOrderDriver extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
    ];

    public function shopOrder()
    {
        return $this->belongsTo(ShopOrder::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->hasMany(ShopOrderDriverStatus::class);
    }
}
