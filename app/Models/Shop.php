<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shop extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

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

    protected $appends = ['rating', 'images'];

    public function getRatingAttribute()
    {
        $rating = ShopRating::where('target_id', $this->id)
            ->where('target_type', 'shop')
            ->avg('rating');

        return $rating ? round($rating, 1) : null;
    }

    public function getImagesAttribute()
    {
        return File::where('source', 'shops')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

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

    public function township()
    {
        return $this->belongsTo(Township::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'users', 'id');
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'favorite_product');
    }
    public function vendor()
    {
        return $this->hasOne(ShopOrderVendor::class);
    }
}
