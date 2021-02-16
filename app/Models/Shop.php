<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'official',
        'enable',
    ];

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

    protected $casts = [
        'enable' => 'boolean',
        'official' => 'boolean',
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

    public function shop_branches()
    {
        return $this->hasMany(ShopBranch::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'favorite_shop');
    }
}