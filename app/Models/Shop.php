<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Shop extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
        'shop_id',
    ];

    protected $casts = [
        'notify_numbers' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_official' => 'boolean',
        'is_enable' => 'boolean',
    ];

    protected $appends = ['rating', 'images', 'covers', 'first_order_date'];

    public function toSearchableArray(): array
    {
        $array = $this->toArray();
        $array['id'] = $this->id;
        return $array;
    }

    public function getFirstOrderDateAttribute()
    {
        $shopOrder = ShopOrder::whereHas('vendors', function ($query) {
            $query->where('shop_id', $this->id);
        })->orderBy('order_date', 'ASC')->first();

        return $shopOrder ? $shopOrder->order_date : null;
    }

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

    public function getCoversAttribute()
    {
        return File::where('source', 'shops')
            ->where('source_id', $this->id)
            ->where('type', 'cover')
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
