<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    use HasFactory;

    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
    protected $fillable = [
        'slug',
        'status',
        'created_by',
        'order_id'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
