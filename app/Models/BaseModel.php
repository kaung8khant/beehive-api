<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BaseModel extends Model
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::guard('users')->check()) {
                $model->created_by = Auth::guard('users')->user()->id;
                $model->updated_by = Auth::guard('users')->user()->id;
            }
        });

        static::saving(function ($model) {
            if (Auth::guard('users')->check()) {
                $model->updated_by = Auth::guard('users')->user()->id;
            }
        });

        static::updating(function ($model) {
            if (Auth::guard('users')->check()) {
                $model->updated_by = Auth::guard('users')->user()->id;
            }
        });
    }

    public function getCreatedByAttribute($value)
    {
        return User::with('roles')->find($value);
    }

    public function getUpdatedByAttribute($value)
    {
        return User::with('roles')->find($value);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
