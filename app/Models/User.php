<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'slug',
        'username',
        'name',
        'phone_number',
        'password',
        'is_enable',
        'is_locked',
    ];

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
        'is_locked' => 'boolean',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function restaurantBranch()
    {
        return $this->belongsTo(RestaurantBranch::class, 'restaurant_branch_id');
    }
}
