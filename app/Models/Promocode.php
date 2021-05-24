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

    public function rules()
    {
        return $this->hasMany(PromocodeRule::class);
    }
}
