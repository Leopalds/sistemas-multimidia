<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Queue;

class ProcessMedia implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, Dispatchable;

    public int $mediaId;

    public function __construct(int $mediaId)
    {
        $this->mediaId = $mediaId;
        $this->onQueue('face'); // mesma fila que o worker Python lê: queues:face
    }

    public function handle(): void
    {
        // O worker Python consome esse job diretamente na fila Redis,
        // então esse handle pode ficar vazio (ou você pode usá-lo em fallback).
    }

    public function tags(): array
    {
        return ['media:'.$this->mediaId];
    }

    public function payload(): array
    {
        // Muitos drivers ignoram isso, mas o importante:
        // o Python está esperando data.media_id

        return ['media_id' => $this->mediaId];
    }

      /**
     * Publica um payload JSON cru no Redis, sem PHP serialization.
     * Obs.: isso NÃO é uma job Laravel; é uma mensagem para seu worker Python.
     */
    public static function dispatchRaw(int $mediaId, string $queue = 'face', ?string $connection = null): void
    {
        $media = Media::find($mediaId);
        
        if (!$media) {
            throw new \Exception("Media not found");
        }

        $payload = json_encode([
            'type'      => $media->type,
            'media_id'  => $mediaId,
            'queued_at' => now()->toIso8601String(),
            'meta'      => $media->meta,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // conexão default do queue.php (ex.: 'redis'), ou a passada por parâmetro
        $conn = $connection ?: config('queue.default');

        // IMPORTANTE: isso empurra o JSON “cru” para a lista Redis da fila.
        // Laravel workers não saberão processar essa mensagem (não tem 'job' nem 'command').
        Queue::connection($conn)->pushRaw($payload, $queue);
    }
}
