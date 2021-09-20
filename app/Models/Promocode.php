<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Promocode extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function rules()
    {
        return $this->hasMany(PromocodeRule::class);
    }

    public function promotion()
    {
        return $this->hasOne(Promotion::class);
    }
}
