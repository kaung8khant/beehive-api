<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Restaurant extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        // 'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
        'commission' => 'float',
    ];

    protected $appends = ['rating', 'images', 'covers', 'first_order_date'];

    public function toSearchableArray(): array
    {
        return $this->toArray();
    }

    public function getFirstOrderDateAttribute()
    {
        $restaurantOrder = RestaurantOrder::where('restaurant_id', $this->id)
            ->orderBy('order_date', 'ASC')->first();

        return $restaurantOrder ? $restaurantOrder->order_date : null;
    }

    public function getRatingAttribute()
    {
        $rating = RestaurantRating::where('target_id', $this->id)
            ->where('target_type', 'restaurant')
            ->avg('rating');

        return $rating ? round($rating, 1) : null;
    }

    public function getImagesAttribute()
    {
        return File::where('source', 'restaurants')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function getCoversAttribute()
    {
        return File::where('source', 'restaurants')
            ->where('source_id', $this->id)
            ->where('type', 'cover')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function availableTags()
    {
        return $this->belongsToMany(RestaurantTag::class, 'restaurant_restaurant_tag_map');
    }

    public function availableCategories()
    {
        return $this->belongsToMany(RestaurantCategory::class, 'restaurant_restaurant_category_map');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function restaurantBranches()
    {
        return $this->hasMany(RestaurantBranch::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'favorite_restaurant');
    }
}
