<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OneTimePassword extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'otp_code',
        'status',
        'message_id',
    ];
}