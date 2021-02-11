<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['name','name_mm', 'slug'];

    public function townships()
    {
        return $this->hasMany(Township::class);
    }
}
