<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ads extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'label',
        'contact_person',
        'company_name',
        'phone_number',
        'email',
        'type',
        'source',
        'created_by'
    ];

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

        return [
            'slug' => $user->slug,
            'name' => $user->name,
            'phone_number' => $user->phone_number,
        ];
    }
}
