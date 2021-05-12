<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'announcement_date',
        'title',
        'description',
    ];

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
