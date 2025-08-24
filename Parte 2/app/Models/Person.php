<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    /** @use HasFactory<\Database\Factories\PersonFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'thumbnail_path'
    ];

    public function media()
    {
        return $this->belongsToMany(Media::class, 'video_hits', 'person_id', 'media_id')
                    ->withPivot(['frame_index', 'timestamp_s', 'distance', 'left', 'top', 'right', 'bottom'])
                    ->withTimestamps();
    }

    public function videoHits()
    {
        return $this->hasMany(VideoHit::class);
    }

    public function imageDetections()
    {
        return $this->belongsToMany(Media::class, 'faces', 'person_id', 'source')
                    ->where('type', 'photo')
                    ->where('status', 'processed')
                    ->whereRaw("JSON_EXTRACT(meta, '$.detections') IS NOT NULL");
    }

    public function allDetections()
    {
        // Combina detecções de vídeos e imagens
        $videoDetections = $this->videoHits()->with('media')->get();
        $imageDetections = $this->imageDetections()->get();
        
        return [
            'videos' => $videoDetections,
            'images' => $imageDetections
        ];
    }
}
