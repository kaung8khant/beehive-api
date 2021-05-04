<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_customer_group_map');
    }
}
