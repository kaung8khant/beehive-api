<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'customer_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        // 'floor' => 'integer',
        // 'latitude' => 'float',
        // 'longitude' => 'float',
    ];

    // public function township()
    // {
    //     return $this->belongsTo(Township::class)->with('city');
    // }
}
