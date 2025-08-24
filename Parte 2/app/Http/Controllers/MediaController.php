<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Jobs\ProcessMedia;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Recupera todas as mídias do banco de dados (fotos e vídeos)
        $media = Media::orderBy('created_at', 'desc')->get();

        // Retorna a resposta Inertia com as mídias
        return Inertia::render('Media/Index', [
            'media' => $media,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render("Media/Upload");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMediaRequest $request)
    {
        $uploaded = [];

        $files = $request->file('files', []);
        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $type = str_contains($file->getMimeType(), 'video') ? 'video' : 'photo';

            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('media', $originalName, 'public');

            $media = Media::create([
                'path' => $path,
                'type' => $type,
                'meta' => null,
            ]);

            ProcessMedia::dispatchRaw($media->id, queue: 'face');
            $uploaded[] = $path;
        }

        if (empty($uploaded)) {
            return back()->withErrors([
                'files' => 'Falha ao enviar os arquivos. Tente novamente após ajustar as configurações do servidor.',
            ]);
        }

        return redirect()->route('media.index')->with('success', count($uploaded) . ' arquivo(s) enviados com sucesso!');
    }
    

    /**
     * Display the specified resource.
     */
    public function show(Media $media)
    {
        // Carrega relacionamentos necessários
        $media->load(['videoHits.person']);
        
        // Para imagens, busca detecções nos metadados
        if ($media->type === 'photo' && isset($media->meta['detections'])) {
            // Obtém as dimensões da imagem
            $imagePath = storage_path('app/public/' . $media->path);
            $imageDimensions = null;
            
            if (file_exists($imagePath)) {
                $imageInfo = getimagesize($imagePath);
                if ($imageInfo) {
                    $imageDimensions = [
                        'width' => $imageInfo[0],
                        'height' => $imageInfo[1]
                    ];
                }
            }
            
            $detections = collect($media->meta['detections'])->map(function ($detection) use ($imageDimensions) {
                // Valida person_id
                $personId = $detection['person_id'] ?? null;
                if ($personId === null || $personId === '') {
                    return null; // Pula detecções inválidas
                }
                
                // Busca o nome da pessoa no banco se não estiver nos metadados
                $personName = $detection['name'] ?? null;
                if ($personName === null || $personName === '') {
                    $person = \App\Models\Person::find($personId);
                    $personName = $person ? $person->name : null;
                }
                
                // Se ainda não tem nome, usa um nome padrão
                if ($personName === null || $personName === '') {
                    $personName = "Pessoa #{$personId}";
                }
                
                // Valida e formata o bbox
                $bbox = $detection['bbox'] ?? [];
                if (!is_array($bbox)) {
                    $bbox = [];
                }
                
                // Garante que todos os campos do bbox estão presentes
                $bbox = array_merge([
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0
                ], $bbox);
                
                // Converte coordenadas absolutas para percentuais se tivermos as dimensões da imagem
                if ($imageDimensions && isset($bbox['left']) && isset($bbox['right']) && isset($bbox['top']) && isset($bbox['bottom'])) {
                    $bbox['left'] = ($bbox['left'] / $imageDimensions['width']) * 100;
                    $bbox['right'] = ($bbox['right'] / $imageDimensions['width']) * 100;
                    $bbox['top'] = ($bbox['top'] / $imageDimensions['height']) * 100;
                    $bbox['bottom'] = ($bbox['bottom'] / $imageDimensions['height']) * 100;
                }
                
                // Valida e formata distance
                $distance = $detection['distance'] ?? 0;
                if (is_numeric($distance) && is_infinite($distance)) {
                    $distance = 1.0; // Valor máximo para distância infinita
                }
                
                return [
                    'person_id' => $personId,
                    'person_name' => $personName,
                    'distance' => $distance,
                    'bbox' => $bbox,
                ];
            })->filter(); // Remove detecções null
        } else {
            $detections = collect();
        }
        
        // Para vídeos, busca hits organizados por frame
        if ($media->type === 'video') {
            $videoHits = $media->videoHits()
                ->with('person')
                ->orderBy('frame_index')
                ->get()
                ->groupBy('frame_index');
        } else {
            $videoHits = collect();
        }
        
        return Inertia::render('Media/Show', [
            'media' => $media,
            'detections' => $detections,
            'videoHits' => $videoHits,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Media $media)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMediaRequest $request, Media $media)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Media $media)
    {
        // Apaga o arquivo fisicamente do sistema de arquivos
        if (Storage::exists('public/' . $media->path)) {
            Storage::delete('public/' . $media->path);
        }

        // Exclui o registro do banco de dados
        $media->delete();

        // Se a requisição espera JSON (AJAX), retorna 204 No Content
        if (request()->wantsJson() || request()->ajax()) {
            return response()->noContent();
        }

        // Retorna para a página de upload com uma mensagem de sucesso
        return redirect()->route('media.create')->with('success', 'Imagem excluída com sucesso!');
    }

}
