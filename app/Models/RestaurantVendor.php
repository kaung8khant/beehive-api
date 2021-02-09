<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantVendor extends Model
{
    use HasFactory;

    protected $fillable = [ 'slug' , 'name' , 'name_mm' , 'address' , 'contactNumber' , 'openingTime' , 'closingTime' , 'enable', 'township_id'] ;

    public function township()
    {
        return $this->hasOne(Township::class);
    }
}
