<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        return DB::table('users')->where('id', $value)->select('slug', 'username', 'name', 'phone_number')->first();
    }

    public function getUpdatedByAttribute($value)
    {
        return DB::table('users')->where('id', $value)->select('slug', 'username', 'name', 'phone_number')->first();
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
