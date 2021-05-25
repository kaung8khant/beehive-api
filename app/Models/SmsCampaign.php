<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsCampaign extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['id'];
}
