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

    protected $fillable =["order_id","item_id","item_name","item_type","amount","quantity","tax","discount","is_deleted"];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
