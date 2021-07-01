<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends BaseModel
{
    use HasFactory;

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
        return $this->menuVariants()->orderBy('price', 'asc')->first();
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

    public function restaurantBranches()
    {
        return $this->belongsToMany(RestaurantBranch::class, 'restaurant_branch_menu_map')->withPivot('is_available');
    }
}
