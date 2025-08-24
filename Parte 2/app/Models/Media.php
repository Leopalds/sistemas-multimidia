<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    /** @use HasFactory<\Database\Factories\MediaFactory> */
    use HasFactory;

    protected $fillable = [
        'path',
        'type',
        'meta',
        'status'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function videoHits()
    {
        return $this->hasMany(VideoHit::class);
    }

    public function people()
    {
        return $this->belongsToMany(Person::class, 'video_hits', 'media_id', 'person_id')
                    ->withPivot(['frame_index', 'timestamp_s', 'distance', 'left', 'top', 'right', 'bottom'])
                    ->withTimestamps();
    }
}
