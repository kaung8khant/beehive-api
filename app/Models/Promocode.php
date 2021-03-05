<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'code',
        'type',
        'usage',
        'amount',
        'description'
    ];

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
