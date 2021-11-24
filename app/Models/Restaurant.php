<?php

namespace App\Models;

use App\Helpers\StringHelper;
use App\Jobs\Algolia\UpdateMenu;
use App\Jobs\Algolia\UpdateRestaurantBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Restaurant extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
        'commission' => 'float',
    ];

    protected $appends = ['rating', 'images', 'covers', 'first_order_date'];

    public static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            $uniqueKey = StringHelper::generateUniqueSlug();
            UpdateRestaurantBranch::dispatch($uniqueKey, $model);

            $uniqueKey = StringHelper::generateUniqueSlug();
            UpdateMenu::dispatch($uniqueKey, $model);
        });
    }

    public function toSearchableArray(): array
    {
        $array = $this->toArray();
        $array['id'] = $this->id;
        return $array;
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
            ->whereIn('extension', ['png', 'jpg', 'jpeg'])
            ->get();
    }

    public function getCoversAttribute()
    {
        return File::where('source', 'restaurants')
            ->where('source_id', $this->id)
            ->where('type', 'cover')
            ->whereIn('extension', ['png', 'jpg', 'jpeg'])
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
