<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Township extends Model
{
    use HasFactory;

    protected $fillable= ['name','name_mm', 'slug','city_id' , 'restaurant_vendor_id'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function shop_branches()
    {
        return $this->hasMany(ShopBranch::class);
    }
}
