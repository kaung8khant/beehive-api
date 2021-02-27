<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'is_official',
        'is_enable',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_official' => 'boolean',
        'is_enable' => 'boolean',
    ];

    public function shopTags()
    {
        return $this->belongsToMany(ShopTag::class, 'tag_shop');
    }

    public function availableCategories()
    {
        return $this->belongsToMany(ShopCategory::class, 'category_shop');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function shopBranches()
    {
        return $this->hasMany(ShopBranch::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'favorite_shop');
    }
}
