<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */

    protected $fillable =["order_id","itemId","itemName","itemType","amount","quantity","tax","discount","isDeleted"];

    protected $casts = [
        'isDeleted' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
