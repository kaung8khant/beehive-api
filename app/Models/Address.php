<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

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

    public function township()
    {
        return $this->belongsTo(Township::class)->with('city');
    }
}
