<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'enable',
        'address',
        'contact_number',
        'opening_time',
        'closing_time',
        'latitude',
        'longitude',
        'restaurant_id',
        'township_id',
    ];

    protected $hidden = [
        'id',
        'restaurant_id',
        'township_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function township()
    {
        return $this->belongsTo(Township::class);
    }
}
