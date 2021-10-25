<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Menu extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'restaurant_id',
        'restaurant_category_id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
        'variants' => 'array',
    ];

    protected $appends = ['images'];

    protected $touches = ['restaurant', 'restaurantCategory'];

    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        $array['id'] = $this->id;
        $array['restaurant_id'] = $this->restaurant ? $this->restaurant->id : null;
        $array['restaurant_category_id'] = $this->restaurantCategory ? $this->restaurantCategory->id : null;

        $array['restaurant_name'] = $this->restaurant ? $this->restaurant->name : null;
        $array['restaurant_category_name'] = $this->restaurantCategory ? $this->restaurantCategory->name : null;

        $array['is_restaurant_enable'] = $this->restaurant ? $this->restaurant->is_enable : null;
        return $array;
    }

    public function getPriceAttribute($value)
    {
        if ($this->cheapestVariant()) {
            return $this->cheapestVariant()->price;
        }

        return $value;
    }

    public function getTaxAttribute($value)
    {
        if ($this->cheapestVariant()) {
            return $this->cheapestVariant()->tax;
        }

        return $value;
    }

    public function getDiscountAttribute($value)
    {
        if ($this->cheapestVariant()) {
            return $this->cheapestVariant()->discount;
        }

        return $value;
    }

    private function cheapestVariant()
    {
        return $this->menuVariants()->where('is_enable', 1)->orderBy('price', 'asc')->first();
    }

    public function getImagesAttribute()
    {
        return File::where('source', 'menus')
            ->where('source_id', $this->id)
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function getIsAvailableAttribute()
    {
        return boolval($this->pivot->is_available);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function restaurantCategory()
    {
        return $this->belongsTo(RestaurantCategory::class);
    }

    public function menuVariations()
    {
        return $this->hasMany(MenuVariation::class);
    }

    public function menuVariants()
    {
        return $this->hasMany(MenuVariant::class);
    }

    public function menuToppings()
    {
        return $this->hasMany(MenuTopping::class);
    }

    public function menuOptions()
    {
        return $this->hasMany(MenuOption::class);
    }

    public function restaurantBranches()
    {
        return $this->belongsToMany(RestaurantBranch::class, 'restaurant_branch_menu_map')->withPivot('is_available');
    }
}
