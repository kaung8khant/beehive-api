<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
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

    protected $guarded = ['id'];

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

    public static function boot()
    {
        parent::boot();

        static::creating(function($model)
        {
            $model->created_by = Auth::guard('users')->user()->id;
            $model->updated_by = Auth::guard('users')->user()->id;
        });

        static::updating(function($model)
        {
            $model->updated_by = Auth::guard('users')->user()->id;
        });
    }

    public function getCreatedByAttribute($value)
    {
        return User::find($value);
    }

    public function getUpdatedByAttribute($value)
    {
        return User::find($value);
    }

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
