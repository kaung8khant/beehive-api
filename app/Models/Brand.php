<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['slug','name'];

    protected $hidden=['id','created_at','updatd_at'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
