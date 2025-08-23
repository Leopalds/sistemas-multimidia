<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaProcessedRequest;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    public function find($id)
    {
        return Media::findOrFail($id);
    }

    public function processed(Request $request, Media $media)
    {
        // 1) Validação "externa"
        $data = $request->all();

        // 2) Tratar status=failed
        if ($data['status'] === 'failed') {
            $meta = $media->meta ?? [];
            $meta['error'] = $data['error'] ?? 'Unknown error';
            $media->update([
                'status' => 'failed',
                'meta'   => $meta,
            ]);
            return response()->json([
                'ok' => true,
                'media' => $media->fresh(),
            ]);
        }

        DB::beginTransaction();
        $meta = $media->meta ?? [];

        if ($media->type === 'photo') {
            // Guardar detections em meta
            $meta['detections'] = $data['detections'];
        }

        if ($media->type === 'video') {
            $meta['fps'] = $data['fps'];
            $meta['frame_skip'] = $data['frame_skip'];
            $meta['hits'] = $data['hits'];

            $rows = [];
            $now = now();

            foreach ($data['hits'] as $hit) {
                $m = $hit['match'];
                $bbox = $m['bbox'];
                $rows[] = [
                    'media_id'    => $media->id,
                    'person_id'   => $m['person_id'],
                    'frame_index' => $hit['frame_index'],
                    'timestamp_s' => $hit['timestamp_s'],
                    'left'        => $bbox['left'],
                    'top'         => $bbox['top'],
                    'right'       => $bbox['right'],
                    'bottom'      => $bbox['bottom'],
                    'distance'    => $m['distance'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            // bulk insert para performance
            if (!empty($rows)) {
                // Se preferir Eloquent: VideoHit::insert($rows);
                DB::table('video_hits')->insert($rows);
            }
        }

        $media->update([
            'status' => 'processed',
            'meta'   => $meta,
        ]);

        DB::commit();

        return response()->json([
            'ok' => true,
            'media' => $media->fresh(),
        ]);
    }
}
