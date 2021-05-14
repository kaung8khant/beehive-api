<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromocodeRule extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'promocode_id',
        'created_at',
        'updated_at',
    ];

    public function promocode()
    {
        return $this->belongsTo(Promocode::class);
    }
}
