<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Page extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function getModifiedByAttribute($value)
    {
        return User::find($value);
    }
}
