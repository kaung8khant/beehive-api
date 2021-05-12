<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ads extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updatd_at',
    ];

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'ads')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function getCreatedByAttribute($value)
    {
        $user = User::find($value);
        if ($user) {
            return [
                'slug' => $user->slug,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
            ];
        }
        return null;
    }
}
