<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    protected $appends = ['primary_address'];

    public function getPrimaryAddressAttribute()
    {
        return $this->addresses->firstWhere('is_primary', 1);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function favoriteRestaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'favorite_restaurant');
    }

    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class, 'favorite_product');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function customerGroups()
    {
        return $this->belongsToMany(CustomerGroup::class, 'customer_customer_group_map');
    }

    public function credit()
    {
        return $this->hasOne(Credit::class);
    }
}
