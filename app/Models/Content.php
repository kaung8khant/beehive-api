<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'updated_at',
    ];

    protected $appends = ['covers'];

    public function getCoversAttribute()
    {
        return File::where('source', 'contents')
            ->where('source_id', $this->id)
            ->where('type', 'cover')
            ->whereIn('extension', ['png', 'jpg', 'jpeg'])
            ->get();
    }
}
