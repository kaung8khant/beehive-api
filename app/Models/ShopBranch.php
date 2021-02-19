<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'address',
        'contact_number',
        'opening_time',
        'closing_time',
        'latitude',
        'longitude',
        'shop_id',
        'township_id',
        'is_enable',
    ];

    protected $hidden = [
        'id',
        'shop_id',
        'township_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function township()
    {
        return $this->belongsTo(Township::class);
    }
}
