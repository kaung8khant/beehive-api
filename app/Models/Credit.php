<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credit extends BaseModel
{
    use HasFactory;

    protected $primaryKey = 'customer_id';

    protected $guarded = [];

    protected $hidden = [
        'customer_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
