<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [ 'slug' , 'name' , 'name_mm' , 'official' , 'enable'] ;

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

    public function shop_tags()
    {
        return $this->belongsToMany(ShopTag::class, 'tag_shop');
    }

    public function shop_categories()
    {
        return $this->belongsToMany(ShopCategory::class, 'category_shop');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
