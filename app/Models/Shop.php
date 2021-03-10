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
        'address',
        'contact_number',
        'opening_time',
        'closing_time',
        'latitude',
        'longitude',
        'shop_id',
        'township_id',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
        'shop_id',
        'township_id',
    ];

    protected $casts = [
        'is_official' => 'boolean',
        'is_enable' => 'boolean',
    ];

    public function availableTags()
    {
        return $this->belongsToMany(ShopTag::class, 'shop_shop_tag_map');
    }

    public function availableCategories()
    {
        return $this->belongsToMany(ShopCategory::class, 'shop_shop_category_map');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'favorite_shop');
    }

    public function township()
    {
        return $this->belongsTo(Township::class);
    }

    public function user(){
        return $this->belongsTo(User::class,'users','id');
    }
}
