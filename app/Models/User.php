<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;

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

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'users')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::guard('users')->check()) {
                $model->created_by = Auth::guard('users')->user()->id;
                $model->updated_by = Auth::guard('users')->user()->id;
            }
        });

        static::updating(function ($model) {
            if (Auth::guard('users')->check()) {
                $model->updated_by = Auth::guard('users')->user()->id;
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getCreatedByAttribute($value)
    {
        return DB::table('users')->where('id', $value)->select('slug', 'username', 'name', 'phone_number')->first();
    }

    public function getUpdatedByAttribute($value)
    {
        return DB::table('users')->where('id', $value)->select('slug', 'username', 'name', 'phone_number')->first();
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

    public function driverOrder()
    {
        return $this->hasMany(RestaurantOrderDriver::class, 'id');
    }
    public function driverShopOrder()
    {
        return $this->hasMany(ShopOrderDriver::class, 'id');
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
