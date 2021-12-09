<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\StringHelper;

class PromocodeRule extends BaseModel
{
    use HasFactory, StringHelper;

    protected $guarded = ['id'];

    protected $hidden = [
        'promocode_id',
        'created_at',
        'updated_at',
    ];
    public function getValueAttribute($value)
    {
        return StringHelper::isJson($value) ? json_decode($value) : strval($value);
    }
    public function promocode()
    {
        return $this->belongsTo(Promocode::class);
    }
}
