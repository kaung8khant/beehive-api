<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantVendor extends Model
{
    use HasFactory;

    protected $fillable = [ 'slug' , 'name' , 'offical' , 'address' , 'contactNumber' , 'openingTime' , 'closingTime' , 'enable'] ;
}
