<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role');
    }
}
