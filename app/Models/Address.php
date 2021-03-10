<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'house_number',
        'floor',
        'street_name',
        'latitude',
        'longitude',
        'is_primary',
        'township_id',
        'customer_id',
    ];

    protected $hidden = [
        'id',
        'township_id',
        'customer_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];
}
