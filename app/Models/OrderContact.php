<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderContact extends Model
{
    use HasFactory;

    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */

    protected $fillable =["order_id","customerId","customerName","phoneNumber","houseNumber","floor","streetName","latitude","longitude"];

    public function order()
    {
        return $this->hasOne(Order::class);
    }
}
