<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RestaurantBranch extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'restaurant_id',
        'township_id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    public function getIsAvailableAttribute()
    {
        return boolval($this->pivot->is_available);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function township()
    {
        return $this->belongsTo(Township::class);
    }

    public function availableMenus()
    {
        return $this->belongsToMany(Menu::class, 'restaurant_branch_menu_map')->withPivot('is_available');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'users', 'id');
    }
}
