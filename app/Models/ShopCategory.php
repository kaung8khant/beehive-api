<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name','name_mm', 'slug'];

    /**
    * The attributes that should be hidden for arrays.
    *
    * @var array
    */
    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];


    public function sub_categories()
    {
        return $this->hasMany(SubCategory::class);
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'category_shop');
    }
}
