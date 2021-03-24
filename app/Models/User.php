<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @OA\Schema(
 *      @OA\Xml(name="User"),
 *      @OA\Property(property="username", type="string", example="username"),
 *      @OA\Property(property="name", type="string", example="name"),
 *      @OA\Property(property="phone_number", type="string", example="phone_number"),
 *      @OA\Property(property="password", type="string",example="password"),
 *      @OA\Property(property="roles", type="array", @OA\Items(oneOf={
 *        @OA\Schema(
 *           type="string",example="role_slug"
 *           ),
 *     })),
 *      @OA\Property(property="is_enable", type="boolean",example="true"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */

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
        'shop_id',
        'restaurant_branch_id',
    ];

    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'pivot',
        'shop_id',
        'restaurant_branch_id',
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
        return $this->belongsToMany(Role::class, 'user_role_map');
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
    
    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }
}
