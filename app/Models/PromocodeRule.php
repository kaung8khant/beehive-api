<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromocodeRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'value',
        'data_type',
        'promocode_id',
    ];

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
