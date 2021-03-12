<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Customer"),
 *      @OA\Property(property="slug", type="string", readOnly=true),
 *      @OA\Property(property="email", type="string", example="email"),
 *      @OA\Property(property="name", type="string", example="Name"),
 *      @OA\Property(property="phone_number", type="string", example="09444421122"),
 *      @OA\Property(property="password", type="string", example="022552dxz2sd"),
 *      @OA\Property(property="date_of_birth", type="string", example="2021-02-19"),
 *      @OA\Property(property="gender", type="string", example="male"),
 * )
 */
class Customer extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'slug',
        'email',
        'name',
        'phone_number',
        'password',
        'gender',
        'date_of_birth',
    ];

    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'verified_at',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'favorite_shop');
    }

    public function favoriteRestaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'favorite_restaurant');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
}
