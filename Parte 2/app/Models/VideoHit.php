<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoHit extends Model
{
    protected $table = 'video_hits';

    protected $fillable = [
        'media_id',
        'person_id',
        'frame_index',
        'timestamp_s',
        'left', 'top', 'right', 'bottom',
        'distance',
    ];

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
