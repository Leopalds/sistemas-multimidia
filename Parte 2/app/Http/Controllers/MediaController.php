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
        //
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
