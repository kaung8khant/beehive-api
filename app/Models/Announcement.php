<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'announcements')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }
}
