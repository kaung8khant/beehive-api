<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'created_by',
    ];

    protected $hidden = [
        'id',
        'order_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
