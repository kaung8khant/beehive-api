<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'message_id',
        'phone_number',
        'message',
        'message_parts',
        'total_characters',
        'encoding',
        'type',
        'status',
        'error_message',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
