<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Media;

class MediaProcessedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'status' => 'required|string|in:processed,failed',
            // Campos comuns opcionais
            'error' => 'nullable|string',
            // Estrutura de detections (imagem)
            'detections' => 'nullable|array',
            'detections.*.person_id' => 'nullable|integer',
            'detections.*.name' => 'nullable|string',
            'detections.*.matched' => 'nullable|boolean',
            'detections.*.distance' => 'nullable|numeric',
            'detections.*.bbox' => 'nullable|array',
            'detections.*.bbox.top' => 'required_with:detections.*.bbox|integer',
            'detections.*.bbox.right' => 'required_with:detections.*.bbox|integer',
            'detections.*.bbox.bottom' => 'required_with:detections.*.bbox|integer',
            'detections.*.bbox.left' => 'required_with:detections.*.bbox|integer',
            // Estrutura de hits (vídeo)
            'hits' => 'nullable|array',
            'hits.*.frame_index' => 'required|integer',
            'hits.*.timestamp_s' => 'required|numeric',
            'hits.*.match' => 'required|array',
            'hits.*.match.person_id' => 'required|integer',
            'hits.*.match.distance' => 'nullable|numeric',
            'hits.*.match.bbox' => 'required|array',
            'hits.*.match.bbox.top' => 'required|integer',
            'hits.*.match.bbox.right' => 'required|integer',
            'hits.*.match.bbox.bottom' => 'required|integer',
            'hits.*.match.bbox.left' => 'required|integer',
            'fps' => 'numeric',
            'frame_skip' => 'integer|min:0',
        ];

        // Regras condicionais conforme tipo da mídia
        $media = $this->route('media');
        if (!$media) {
            $mediaId = $this->route('id');
            if ($mediaId) {
                $media = Media::find($mediaId);
            }
        }

        if ($this->input('status') === 'processed' && $media) {
            if ($media->type === 'image') {
                $rules['detections'] = ['required','array'];
            } elseif ($media->type === 'video') {
                $rules['hits'] = ['required','array'];
                $rules['fps'] = ['required','numeric'];
                $rules['frame_skip'] = ['required','integer','min:0'];
            }
        }

        return $rules;
    }
}
